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

namespace IcuParser\Validation;

use IcuParser\Node\MessageNode;
use IcuParser\Node\OptionNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\TextNode;
use IcuParser\NodeVisitor\AbstractNodeVisitor;

/**
 * Semantic validator for ICU MessageFormat AST.
 */
final class SemanticValidator extends AbstractNodeVisitor
{
    private ValidationResult $result;

    private string $source = '';

    /**
     * @var array<int, array{type: string, name: string}>
     */
    private array $contextStack = [];

    public function validate(MessageNode $message, string $source): ValidationResult
    {
        $this->result = new ValidationResult();
        $this->source = $source;
        $this->contextStack = [];

        $message->accept($this);

        return $this->result;
    }

    public function visitSelect(SelectNode $node): void
    {
        $this->pushContext('select', $node->name);
        $this->validateOptions($node->options, 'select', $node->name, $node->startPosition);
        parent::visitSelect($node);
        $this->popContext();
    }

    public function visitPlural(PluralNode $node): void
    {
        $this->pushContext('plural', $node->name);
        $this->validateOptions($node->options, 'plural', $node->name, $node->startPosition);
        parent::visitPlural($node);
        $this->popContext();
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node): void
    {
        $this->pushContext('selectordinal', $node->name);
        $this->validateOptions($node->options, 'selectordinal', $node->name, $node->startPosition);
        parent::visitSelectOrdinal($node);
        $this->popContext();
    }

    public function visitOption(OptionNode $node): void
    {
        if ($this->isMessageEmpty($node->message)) {
            $context = $this->currentContext();
            $suffix = null === $context
                ? ''
                : sprintf(' in %s argument "%s"', $context['type'], $context['name']);

            $this->addError(
                sprintf('Empty option message for selector "%s"%s.', $node->selector, $suffix),
                $node->startPosition,
                'validator.empty_option',
            );
        }

        parent::visitOption($node);
    }

    /**
     * @param array<int, OptionNode> $options
     */
    private function validateOptions(array $options, string $type, string $name, int $position): void
    {
        $seen = [];
        $hasOther = false;

        foreach ($options as $option) {
            $key = $option->selector;
            if ('other' === $key) {
                $hasOther = true;
            }

            if (isset($seen[$key])) {
                $this->addError(
                    sprintf('Duplicate option "%s" in %s argument "%s".', $key, $type, $name),
                    $option->startPosition,
                    'validator.duplicate_option',
                );

                continue;
            }

            $seen[$key] = true;
        }

        if (!$hasOther) {
            $this->addError(
                sprintf('Missing required "other" option in %s argument "%s".', $type, $name),
                $position,
                'validator.missing_other',
            );
        }
    }

    private function isMessageEmpty(MessageNode $message): bool
    {
        if ([] === $message->parts) {
            return true;
        }

        foreach ($message->parts as $part) {
            if ($part instanceof TextNode) {
                if ('' !== trim($part->text)) {
                    return false;
                }

                continue;
            }

            if ($part instanceof PoundNode) {
                return false;
            }

            return false;
        }

        return true;
    }

    private function addError(string $message, int $position, string $code): void
    {
        $this->result->addError(new ValidationError($message, $position, $this->source, $code));
    }

    private function pushContext(string $type, string $name): void
    {
        $this->contextStack[] = ['type' => $type, 'name' => $name];
    }

    private function popContext(): void
    {
        array_pop($this->contextStack);
    }

    /**
     * @return array{type: string, name: string}|null
     */
    private function currentContext(): ?array
    {
        if ([] === $this->contextStack) {
            return null;
        }

        return $this->contextStack[array_key_last($this->contextStack)];
    }
}
