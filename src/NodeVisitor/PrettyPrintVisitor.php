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

namespace IcuParser\NodeVisitor;

use IcuParser\Formatter\FormatOptions;
use IcuParser\Node\ChoiceNode;
use IcuParser\Node\DurationNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\OptionNode;
use IcuParser\Node\OrdinalNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\SpelloutNode;
use IcuParser\Node\TextNode;

/**
 * Pretty formats an ICU message without evaluating it.
 *
 * @implements NodeVisitorInterface<string>
 */
final class PrettyPrintVisitor implements NodeVisitorInterface
{
    private bool $pluralContext = false;
    private bool $choiceContext = false;

    public function __construct(
        private readonly FormatOptions $options,
        private readonly ?TokenStylerInterface $styler = null,
    ) {}

    public function visitMessage(MessageNode $node): string
    {
        return $this->formatMessage($node, $this->pluralContext, $this->choiceContext);
    }

    public function visitText(TextNode $node): string
    {
        $text = $this->escapeText($node->text, $this->pluralContext, $this->choiceContext);

        return $this->styleToken($text, 'text');
    }

    public function visitSimpleArgument(SimpleArgumentNode $node): string
    {
        return $this->style('{', 'brace')
            .$this->styleToken($node->name, 'argument')
            .$this->style('}', 'brace');
    }

    public function visitFormattedArgument(FormattedArgumentNode $node): string
    {
        $output = $this->style('{', 'brace')
            .$this->styleToken($node->name, 'argument')
            .$this->style(',', 'punctuation')
            .$this->style(' ', 'whitespace')
            .$this->styleToken($node->format, 'type');

        if (null !== $node->style && '' !== $node->style) {
            $output .= $this->style(',', 'punctuation')
                .$this->style(' ', 'whitespace')
                .$this->styleToken($node->style, 'style');
        }

        return $output.$this->style('}', 'brace');
    }

    public function visitSelect(SelectNode $node): string
    {
        return $this->formatOptionsBlock($node->name, 'select', $node->options, null, false);
    }

    public function visitPlural(PluralNode $node): string
    {
        return $this->formatOptionsBlock($node->name, 'plural', $node->options, $node->offset, true);
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node): string
    {
        return $this->formatOptionsBlock($node->name, 'selectordinal', $node->options, $node->offset, true);
    }

    public function visitOption(OptionNode $node): string
    {
        return $this->styleToken($node->selector, 'selector')
            .$this->style('{', 'brace')
            .$this->formatMessage($node->message, $this->pluralContext, $this->choiceContext)
            .$this->style('}', 'brace')
            .$this->style(' ', 'whitespace');
    }

    public function visitPound(PoundNode $node): string
    {
        return $this->style('#', 'number');
    }

    public function visitSpellout(SpelloutNode $node): string
    {
        return $this->visitFormattedArgument($node);
    }

    public function visitOrdinal(OrdinalNode $node): string
    {
        return $this->visitFormattedArgument($node);
    }

    public function visitDuration(DurationNode $node): string
    {
        return $this->visitFormattedArgument($node);
    }

    public function visitChoice(ChoiceNode $node): string
    {
        return $this->formatChoice($node);
    }

    /**
     * @param array<int, OptionNode> $optionNodes
     */
    private function formatOptionsBlock(
        string $name,
        string $type,
        array $optionNodes,
        int|float|null $offset,
        bool $pluralContext,
    ): string {
        $lineBreak = $this->options->lineBreak;
        $indent = $this->options->indent;

        $output = $this->style('{', 'brace')
            .$this->styleToken($name, 'argument')
            .$this->style(',', 'punctuation')
            .$this->style(' ', 'whitespace')
            .$this->styleToken($type, 'keyword')
            .$this->style(',', 'punctuation');

        if (null !== $offset) {
            $output .= $this->style(' ', 'whitespace')
                .$this->styleToken('offset:', 'keyword')
                .$this->styleToken($this->formatNumber($offset), 'number');
        }

        $output .= $lineBreak;

        $selectorWidth = $this->options->alignSelectors ? $this->maxSelectorLength($optionNodes) : 0;

        foreach ($optionNodes as $index => $option) {
            if ($index > 0) {
                $output .= $lineBreak;
            }

            $selector = $option->selector;
            $padding = $selectorWidth > 0 ? $selectorWidth - \strlen($selector) : 0;

            $output .= $indent
                .$this->styleToken($selector, 'selector');
            if ($padding > 0) {
                $output .= $this->style(str_repeat(' ', $padding), 'whitespace');
            }
            $output .= $this->style(' ', 'whitespace');
            $output .= $this->formatOptionMessage($option->message, $indent, $pluralContext, false);
        }

        return $output.$lineBreak.$this->style('}', 'brace');
    }

    private function formatChoice(ChoiceNode $node): string
    {
        $lineBreak = $this->options->lineBreak;
        $indent = $this->options->indent;

        $output = $this->style('{', 'brace')
            .$this->styleToken($node->name, 'argument')
            .$this->style(',', 'punctuation')
            .$this->style(' ', 'whitespace')
            .$this->styleToken('choice', 'keyword')
            .$this->style(',', 'punctuation')
            .$lineBreak;

        foreach ($node->options as $index => $option) {
            if ($index > 0) {
                $output .= $lineBreak;
            }

            $output .= $indent;
            if ($index > 0) {
                $output .= $this->style('|', 'punctuation')
                    .$this->style(' ', 'whitespace');
            }

            $operator = $option->isExclusive ? '<' : '#';
            $message = $this->formatMessage($option->message, false, true);
            $message = $this->indentFollowingLines($message, $indent, $lineBreak);

            $output .= $this->styleToken($this->formatNumber($option->limit), 'number')
                .$this->style($operator, 'punctuation')
                .$message;
        }

        return $output.$lineBreak.$this->style('}', 'brace');
    }

    private function formatOptionMessage(
        MessageNode $message,
        string $baseIndent,
        bool $pluralContext,
        bool $choiceContext,
    ): string {
        $lineBreak = $this->options->lineBreak;
        $content = $this->formatMessage($message, $pluralContext, $choiceContext);
        if ($this->isInlineMessage($message) && !str_contains($content, $lineBreak)) {
            return $this->style('{', 'brace').$content.$this->style('}', 'brace');
        }

        $content = $this->indentFollowingLines($content, $baseIndent, $lineBreak);

        return $this->style('{', 'brace').$content.$this->style('}', 'brace');
    }

    private function formatMessage(MessageNode $message, bool $pluralContext, bool $choiceContext): string
    {
        $previousPlural = $this->pluralContext;
        $previousChoice = $this->choiceContext;

        $this->pluralContext = $pluralContext;
        $this->choiceContext = $choiceContext;

        $output = '';
        foreach ($message->parts as $part) {
            $output .= $part->accept($this);
        }

        $this->pluralContext = $previousPlural;
        $this->choiceContext = $previousChoice;

        return $output;
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

    private function styleToken(string $content, string $type): string
    {
        $content = $this->escapeToken($content);

        return $this->style($content, $type);
    }

    private function style(string $content, string $type): string
    {
        return null === $this->styler ? $content : $this->styler->style($content, $type);
    }

    private function escapeToken(string $string): string
    {
        return null === $this->styler ? $string : $this->styler->escapeToken($string);
    }
}
