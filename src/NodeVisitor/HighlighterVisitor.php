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
 * Base visitor for highlighting ICU message syntax.
 *
 * @implements NodeVisitorInterface<string>
 */
abstract class HighlighterVisitor implements NodeVisitorInterface, TokenStylerInterface
{
    public function visitMessage(MessageNode $node): string
    {
        $output = '';
        foreach ($node->parts as $part) {
            $output .= $part->accept($this);
        }

        return $output;
    }

    public function visitText(TextNode $node): string
    {
        return $this->wrap($this->escape($node->text), 'text');
    }

    public function visitSimpleArgument(SimpleArgumentNode $node): string
    {
        return $this->wrap('{', 'brace')
            .$this->wrap($this->escape($node->name), 'argument')
            .$this->wrap('}', 'brace');
    }

    public function visitFormattedArgument(FormattedArgumentNode $node): string
    {
        $output = $this->wrap('{', 'brace')
            .$this->wrap($this->escape($node->name), 'argument')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace')
            .$this->wrap($this->escape($node->format), 'type');

        if (null !== $node->style) {
            $output .= $this->wrap(',', 'punctuation')
                .$this->wrap(' ', 'whitespace')
                .$this->wrap($this->escape($node->style), 'style');
        }

        $output .= $this->wrap('}', 'brace');

        return $output;
    }

    public function visitSelect(SelectNode $node): string
    {
        return $this->renderSelector($node->name, 'select', $node->options);
    }

    public function visitPlural(PluralNode $node): string
    {
        $output = $this->wrap('{', 'brace')
            .$this->wrap($this->escape($node->name), 'argument')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace')
            .$this->wrap('plural', 'keyword')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace');

        if (null !== $node->offset) {
            $output .= $this->wrap('offset:', 'keyword')
                .$this->wrap(':', 'punctuation')
                .$this->wrap((string) $node->offset, 'number')
                .$this->wrap(' ', 'whitespace');
        }

        foreach ($node->options as $option) {
            $output .= $option->accept($this);
        }

        $output .= $this->wrap('}', 'brace');

        return $output;
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node): string
    {
        return $this->renderSelector($node->name, 'selectordinal', $node->options);
    }

    public function visitOption(OptionNode $node): string
    {
        $output = $this->wrap($this->escape($node->selector), 'selector')
            .$this->wrap('{', 'brace')
            .$node->message->accept($this)
            .$this->wrap('}', 'brace')
            .$this->wrap(' ', 'whitespace');

        return $output;
    }

    public function visitPound(PoundNode $node): string
    {
        return $this->wrap('#', 'number');
    }

    public function visitSpellout(SpelloutNode $node): string
    {
        return $this->renderTypeArgument($node->name, $node->format, $node->style);
    }

    public function visitOrdinal(OrdinalNode $node): string
    {
        return $this->renderTypeArgument($node->name, $node->format, $node->style);
    }

    public function visitDuration(DurationNode $node): string
    {
        return $this->renderTypeArgument($node->name, $node->format, $node->style);
    }

    public function visitChoice(ChoiceNode $node): string
    {
        $output = $this->wrap('{', 'brace')
            .$this->wrap($this->escape($node->name), 'argument')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace')
            .$this->wrap('choice', 'keyword')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace');

        foreach ($node->options as $index => $option) {
            if (0 < $index) {
                $output .= $this->wrap('|', 'punctuation')
                    .$this->wrap(' ', 'whitespace');
            }

            $operator = $option->isExclusive ? '<' : '#';

            $output .= $this->wrap($this->escape((string) $option->limit), 'number')
                .$this->wrap($operator, 'punctuation')
                .$option->message->accept($this);
        }

        $output .= $this->wrap('}', 'brace');

        return $output;
    }

    final public function style(string $content, string $type): string
    {
        return $this->wrap($content, $type);
    }

    final public function escapeToken(string $string): string
    {
        return $this->escape($string);
    }

    /**
     * @param array<int, OptionNode> $options
     */
    protected function renderSelector(string $name, string $type, array $options): string
    {
        $output = $this->wrap('{', 'brace')
            .$this->wrap($this->escape($name), 'argument')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace')
            .$this->wrap($type, 'keyword')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace');

        foreach ($options as $option) {
            $output .= $option->accept($this);
        }

        $output .= $this->wrap('}', 'brace');

        return $output;
    }

    /**
     * @param array<string, string>|string|null $styleOrOptions
     */
    protected function renderTypeArgument(string $name, string $type, string|array|null $styleOrOptions = null): string
    {
        $output = $this->wrap('{', 'brace')
            .$this->wrap($this->escape($name), 'argument')
            .$this->wrap(',', 'punctuation')
            .$this->wrap(' ', 'whitespace')
            .$this->wrap($this->escape($type), 'keyword');

        // Handle string style (for ordinal, spellout, duration)
        if (\is_string($styleOrOptions)) {
            $output .= $this->wrap(',', 'punctuation')
                .$this->wrap(' ', 'whitespace')
                .$this->wrap($this->escape($styleOrOptions), 'style');
        }

        // Handle array options (for other types)
        if (\is_array($styleOrOptions)) {
            foreach ($styleOrOptions as $key => $value) {
                $output .= $this->wrap(',', 'punctuation')
                    .$this->wrap(' ', 'whitespace')
                    .$this->wrap($this->escape((string) $key), 'option')
                    .$this->wrap('=', 'punctuation')
                    .$this->wrap($this->escape((string) $value), 'string');
            }
        }

        $output .= $this->wrap('}', 'brace');

        return $output;
    }

    /**
     * Wraps content with styling based on the element type.
     */
    abstract protected function wrap(string $content, string $type): string;

    /**
     * Escapes special characters for the output format.
     */
    abstract protected function escape(string $string): string;
}
