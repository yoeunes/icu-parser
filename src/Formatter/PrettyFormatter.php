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

namespace IcuParser\Formatter;

use IcuParser\Node\ChoiceNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\NodeInterface;
use IcuParser\Node\OptionNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\TextNode;

/**
 * Pretty formats an ICU message without evaluating it.
 */
final readonly class PrettyFormatter
{
    public function __construct(private FormatOptions $defaultOptions = new FormatOptions()) {}

    public function format(MessageNode $message, ?FormatOptions $options = null): string
    {
        $options ??= $this->defaultOptions;

        return $this->formatMessage($message, $options, false, false);
    }

    private function formatMessage(MessageNode $message, FormatOptions $options, bool $pluralContext, bool $choiceContext): string
    {
        $output = '';

        foreach ($message->parts as $part) {
            $output .= $this->formatNode($part, $options, $pluralContext, $choiceContext);
        }

        return $output;
    }

    private function formatNode(NodeInterface $node, FormatOptions $options, bool $pluralContext, bool $choiceContext): string
    {
        return match (true) {
            $node instanceof TextNode => $this->escapeText($node->text, $pluralContext, $choiceContext),
            $node instanceof PoundNode => '#',
            $node instanceof SimpleArgumentNode => '{'.$node->name.'}',
            $node instanceof SelectNode => $this->formatSelect($node, $options),
            $node instanceof PluralNode => $this->formatPlural($node, $options),
            $node instanceof SelectOrdinalNode => $this->formatSelectOrdinal($node, $options),
            $node instanceof ChoiceNode => $this->formatChoice($node, $options),
            $node instanceof FormattedArgumentNode => $this->formatFormattedArgument($node),
            default => '',
        };
    }

    private function formatFormattedArgument(FormattedArgumentNode $node): string
    {
        $output = '{'.$node->name.', '.$node->format;
        if (null !== $node->style && '' !== $node->style) {
            $output .= ', '.$node->style;
        }

        return $output.'}';
    }

    private function formatSelect(SelectNode $node, FormatOptions $options): string
    {
        return $this->formatOptionsBlock($node->name, 'select', $node->options, null, $options, false);
    }

    private function formatPlural(PluralNode $node, FormatOptions $options): string
    {
        return $this->formatOptionsBlock($node->name, 'plural', $node->options, $node->offset, $options, true);
    }

    private function formatSelectOrdinal(SelectOrdinalNode $node, FormatOptions $options): string
    {
        return $this->formatOptionsBlock($node->name, 'selectordinal', $node->options, $node->offset, $options, true);
    }

    /**
     * @param array<int, OptionNode> $optionNodes
     */
    private function formatOptionsBlock(
        string $name,
        string $type,
        array $optionNodes,
        int|float|null $offset,
        FormatOptions $options,
        bool $pluralContext,
    ): string {
        $lineBreak = $options->lineBreak;
        $indent = $options->indent;

        $output = '{'.$name.', '.$type.',';
        if (null !== $offset) {
            $output .= ' offset:'.$this->formatNumber($offset);
        }
        $output .= $lineBreak;

        $selectorWidth = $options->alignSelectors ? $this->maxSelectorLength($optionNodes) : 0;

        foreach ($optionNodes as $index => $option) {
            if ($index > 0) {
                $output .= $lineBreak;
            }

            $selector = $option->selector;
            if ($selectorWidth > 0) {
                $selector = str_pad($selector, $selectorWidth);
            }

            $output .= $indent.$selector.' ';
            $output .= $this->formatOptionMessage($option->message, $options, $indent, $pluralContext, false);
        }

        $output .= $lineBreak.'}';

        return $output;
    }

    private function formatChoice(ChoiceNode $node, FormatOptions $options): string
    {
        $lineBreak = $options->lineBreak;
        $indent = $options->indent;

        $output = '{'.$node->name.', choice,'.$lineBreak;

        foreach ($node->options as $index => $option) {
            if ($index > 0) {
                $output .= $lineBreak;
            }

            $prefix = $indent;
            if ($index > 0) {
                $prefix .= '| ';
            }

            $operator = $option->isExclusive ? '<' : '#';
            $limit = $this->formatNumber($option->limit);
            $message = $this->formatMessage($option->message, $options, false, true);
            $message = $this->indentFollowingLines($message, $indent, $lineBreak);

            $output .= $prefix.$limit.$operator.$message;
        }

        $output .= $lineBreak.'}';

        return $output;
    }

    private function formatOptionMessage(
        MessageNode $message,
        FormatOptions $options,
        string $baseIndent,
        bool $pluralContext,
        bool $choiceContext,
    ): string {
        if ($this->isInlineMessage($message)) {
            return '{'.$this->formatMessage($message, $options, $pluralContext, $choiceContext).'}';
        }

        $lineBreak = $options->lineBreak;
        $content = $this->formatMessage($message, $options, $pluralContext, $choiceContext);
        $content = $this->indentMultiline($content, $baseIndent.$options->indent, $lineBreak);

        return '{'.$lineBreak.$content.$lineBreak.$baseIndent.'}';
    }

    private function isInlineMessage(MessageNode $message): bool
    {
        foreach ($message->parts as $part) {
            if ($part instanceof SelectNode
                || $part instanceof PluralNode
                || $part instanceof SelectOrdinalNode
                || $part instanceof ChoiceNode
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, OptionNode> $options
     */
    private function maxSelectorLength(array $options): int
    {
        $lengths = array_map(
            static fn (OptionNode $option): int => \strlen($option->selector),
            $options,
        );

        return [] === $lengths ? 0 : max($lengths);
    }

    private function formatNumber(int|float $number): string
    {
        return (string) $number;
    }

    private function indentMultiline(string $text, string $prefix, string $lineBreak): string
    {
        if ('' === $lineBreak) {
            return $text;
        }

        $lines = explode($lineBreak, $text);

        foreach ($lines as $index => $line) {
            $lines[$index] = $prefix.$line;
        }

        return implode($lineBreak, $lines);
    }

    private function indentFollowingLines(string $text, string $prefix, string $lineBreak): string
    {
        if ('' === $lineBreak) {
            return $text;
        }

        if (!str_contains($text, $lineBreak)) {
            return $text;
        }

        $lines = explode($lineBreak, $text);
        $first = array_shift($lines);

        foreach ($lines as $index => $line) {
            $lines[$index] = $prefix.$line;
        }

        return (string) $first.$lineBreak.implode($lineBreak, $lines);
    }

    private function escapeText(string $text, bool $pluralContext, bool $choiceContext): string
    {
        $text = str_replace("'", "''", $text);
        $text = str_replace('{', "'{'", $text);
        $text = str_replace('}', "'}'", $text);

        if ($pluralContext) {
            $text = str_replace('#', "'#'", $text);
        }

        if ($choiceContext) {
            $text = str_replace('|', "'|'", $text);
        }

        return $text;
    }
}
