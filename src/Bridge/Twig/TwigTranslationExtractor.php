<?php

declare(strict_types=1);

/*
 * This file is part of the IcuParser package.
 *
 * (c) Younes ENNAJI <younes.ennaji.pro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IcuParser\Bridge\Twig;

use IcuParser\Type\ParameterType;
use IcuParser\Usage\TranslationUsage;
use Symfony\Bridge\Twig\Node\TransNode;
use Twig\Environment;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;
use Twig\Source;

final readonly class TwigTranslationExtractor
{
    public function __construct(private Environment $twig) {}

    /**
     * @return list<TranslationUsage>
     */
    public function extractFromFile(string $path): array
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            return [];
        }

        return $this->extractFromSource($contents, $path);
    }

    /**
     * @return list<TranslationUsage>
     */
    public function extractFromSource(string $source, string $path): array
    {
        $stream = $this->twig->tokenize(new Source($source, $path, $path));
        $module = $this->twig->parse($stream);

        $usages = [];
        $this->walk($module, $usages, $path);

        return $usages;
    }

    /**
     * @param list<TranslationUsage> $usages
     */
    private function walk(Node $node, array &$usages, string $path): void
    {
        if ($node instanceof FilterExpression) {
            $filter = $this->resolveFilterName($node);
            if ('trans' === $filter) {
                $usage = $this->extractTransFilter($node, $path);
                if (null !== $usage) {
                    $usages[] = $usage;
                }
            }
        }

        $tagUsage = $this->extractTransTag($node, $path);
        if (null !== $tagUsage) {
            $usages[] = $tagUsage;
        }

        foreach ($node as $child) {
            $this->walk($child, $usages, $path);
        }
    }

    private function resolveFilterName(FilterExpression $node): ?string
    {
        $name = $node->getAttribute('name');
        if (\is_string($name) && '' !== $name) {
            return $name;
        }

        if ($node->hasNode('filter')) {
            $filterNode = $node->getNode('filter');
            $resolved = $this->resolveConstantString($filterNode);
            if (null !== $resolved) {
                return $resolved;
            }
        }

        if ($node->hasNode('name')) {
            $nameNode = $node->getNode('name');
            $resolved = $this->resolveConstantString($nameNode);
            if (null !== $resolved) {
                return $resolved;
            }
        }

        return null;
    }

    private function extractTransFilter(FilterExpression $node, string $path): ?TranslationUsage
    {
        $id = $this->resolveConstantString($node->getNode('node'));
        if (null === $id) {
            return null;
        }

        $arguments = $node->hasNode('arguments') ? $node->getNode('arguments') : null;
        $argumentNodes = $this->resolveFilterArguments($arguments);

        $parameters = $this->extractArrayLiteral($argumentNodes[0] ?? null);
        $domain = $this->resolveConstantString($argumentNodes[1] ?? null);

        return new TranslationUsage(
            $id,
            $parameters,
            $path,
            $node->getTemplateLine(),
            $domain,
        );
    }

    private function extractTransTag(Node $node, string $path): ?TranslationUsage
    {
        if (!$this->isTransNode($node)) {
            return null;
        }

        $body = $node->hasNode('body') ? $node->getNode('body') : null;
        if (!$body instanceof Node) {
            return null;
        }

        $message = $this->resolveTransBody($body);
        if (null === $message) {
            return null;
        }

        $variables = $node->hasNode('vars') ? $node->getNode('vars') : null;
        $parameters = $this->extractArrayLiteral($variables);
        $domain = $this->resolveConstantString($node->hasNode('domain') ? $node->getNode('domain') : null);

        $line = $node->getTemplateLine();

        return new TranslationUsage(
            $message,
            $parameters,
            $path,
            $line > 0 ? $line : null,
            $domain,
        );
    }

    /**
     * @return array<int, Node>
     */
    private function resolveFilterArguments(?Node $arguments): array
    {
        if ($arguments instanceof ArrayExpression) {
            return [$arguments];
        }

        if (!$arguments instanceof Node) {
            return [];
        }

        $nodes = [];
        foreach ($arguments as $child) {
            $nodes[] = $child;
        }

        return $nodes;
    }

    /**
     * @return array<string, ParameterType>
     */
    private function extractArrayLiteral(?Node $node): array
    {
        if (!$node instanceof ArrayExpression) {
            return [];
        }

        $pairs = $this->resolveArrayPairs($node);
        $resolved = [];

        foreach ($pairs as [$keyNode, $valueNode]) {
            $key = $this->resolveConstantString($keyNode);
            if (null === $key) {
                continue;
            }

            $resolved[$key] = $this->mapExpressionType($valueNode);
        }

        return $resolved;
    }

    /**
     * @return list<array{0: Node, 1: Node}>
     */
    private function resolveArrayPairs(ArrayExpression $node): array
    {
        $pairs = $node->getKeyValuePairs();
        if ([] === $pairs) {
            return [];
        }

        $normalized = [];
        foreach ($pairs as $pair) {
            if (!\is_array($pair)) {
                continue;
            }

            if (isset($pair['key'], $pair['value']) && $pair['key'] instanceof Node && $pair['value'] instanceof Node) {
                $normalized[] = [$pair['key'], $pair['value']];

                continue;
            }

            if (isset($pair[0], $pair[1]) && $pair[0] instanceof Node && $pair[1] instanceof Node) {
                $normalized[] = [$pair[0], $pair[1]];
            }
        }

        return $normalized;
    }

    private function resolveConstantString(?Node $node): ?string
    {
        if ($node instanceof ConstantExpression) {
            $value = $node->getAttribute('value');

            return \is_string($value) ? $value : null;
        }

        return null;
    }

    private function resolveTransBody(Node $node): ?string
    {
        $buffer = '';

        if ($node instanceof ConstantExpression) {
            $value = $node->getAttribute('value');
            if (\is_string($value)) {
                return trim($value);
            }
        }

        if (\is_a($node, 'Twig\\Node\\TextNode')) {
            $data = $node->getAttribute('data');
            if (\is_string($data)) {
                return trim($data);
            }
        }

        foreach ($node as $child) {
            if (\is_a($child, 'Twig\\Node\\TextNode')) {
                $data = $child->getAttribute('data');
                if (\is_string($data)) {
                    $buffer .= $data;
                }

                continue;
            }

            $inner = $this->resolveTransBody($child);
            if (null !== $inner) {
                $buffer .= $inner;
            }
        }

        $buffer = trim($buffer);

        return '' === $buffer ? null : $buffer;
    }

    private function mapExpressionType(Node $node): ParameterType
    {
        if ($node instanceof ConstantExpression) {
            $value = $node->getAttribute('value');

            if (\is_int($value) || \is_float($value)) {
                return ParameterType::NUMBER;
            }

            if (\is_string($value)) {
                return ParameterType::STRING;
            }
        }

        return ParameterType::MIXED;
    }

    private function isTransNode(Node $node): bool
    {
        if (\class_exists(TransNode::class) && \is_a($node, 'Symfony\\Bridge\\Twig\\Node\\TransNode')) {
            return true;
        }

        if (\class_exists('Twig\\Extra\\Translation\\Node\\TransNode') && \is_a($node, 'Twig\\Extra\\Translation\\Node\\TransNode')) {
            return true;
        }

        return false;
    }
}
