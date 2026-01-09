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

use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\OptionNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\TextNode;

/**
 * Converts the AST into an array for debugging or serialization.
 *
 * @implements NodeVisitorInterface<array<string, mixed>>
 */
final class AstDumper implements NodeVisitorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function dump(MessageNode $node): array
    {
        return $node->accept($this);
    }

    public function visitMessage(MessageNode $node)
    {
        return [
            'type' => 'Message',
            'start' => $node->startPosition,
            'end' => $node->endPosition,
            'parts' => array_map(fn ($part) => $part->accept($this), $node->parts),
        ];
    }

    public function visitText(TextNode $node)
    {
        return [
            'type' => 'Text',
            'text' => $node->text,
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitSimpleArgument(SimpleArgumentNode $node)
    {
        return [
            'type' => 'Argument',
            'name' => $node->name,
            'format' => null,
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitFormattedArgument(FormattedArgumentNode $node)
    {
        return [
            'type' => 'Argument',
            'name' => $node->name,
            'format' => $node->format,
            'style' => $node->style,
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitSelect(SelectNode $node)
    {
        return [
            'type' => 'Select',
            'name' => $node->name,
            'options' => array_map(fn ($option) => $option->accept($this), $node->options),
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitPlural(PluralNode $node)
    {
        return [
            'type' => 'Plural',
            'name' => $node->name,
            'offset' => $node->offset,
            'options' => array_map(fn ($option) => $option->accept($this), $node->options),
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node)
    {
        return [
            'type' => 'SelectOrdinal',
            'name' => $node->name,
            'offset' => $node->offset,
            'options' => array_map(fn ($option) => $option->accept($this), $node->options),
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitOption(OptionNode $node)
    {
        return [
            'type' => 'Option',
            'selector' => $node->selector,
            'explicit' => $node->explicit,
            'explicitValue' => $node->explicitValue,
            'message' => $node->message->accept($this),
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }

    public function visitPound(PoundNode $node)
    {
        return [
            'type' => 'Pound',
            'start' => $node->startPosition,
            'end' => $node->endPosition,
        ];
    }
}
