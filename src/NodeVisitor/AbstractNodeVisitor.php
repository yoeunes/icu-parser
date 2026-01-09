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
 * Default visitor that recursively traverses child nodes.
 *
 * @implements NodeVisitorInterface<void>
 */
abstract class AbstractNodeVisitor implements NodeVisitorInterface
{
    public function visitMessage(MessageNode $node): void
    {
        foreach ($node->parts as $part) {
            $part->accept($this);
        }
    }

    public function visitText(TextNode $node): void {}

    public function visitSimpleArgument(SimpleArgumentNode $node): void {}

    public function visitFormattedArgument(FormattedArgumentNode $node): void {}

    public function visitSelect(SelectNode $node): void
    {
        foreach ($node->options as $option) {
            $option->accept($this);
        }
    }

    public function visitPlural(PluralNode $node): void
    {
        foreach ($node->options as $option) {
            $option->accept($this);
        }
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node): void
    {
        foreach ($node->options as $option) {
            $option->accept($this);
        }
    }

    public function visitOption(OptionNode $node): void
    {
        $node->message->accept($this);
    }

    public function visitPound(PoundNode $node): void {}
}
