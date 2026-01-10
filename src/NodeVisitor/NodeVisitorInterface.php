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
 * Visitor contract for ICU message AST nodes.
 *
 * @template-covariant TReturn
 */
interface NodeVisitorInterface
{
    /**
     * @return TReturn
     */
    public function visitMessage(MessageNode $node);

    /**
     * @return TReturn
     */
    public function visitText(TextNode $node);

    /**
     * @return TReturn
     */
    public function visitSimpleArgument(SimpleArgumentNode $node);

    /**
     * @return TReturn
     */
    public function visitFormattedArgument(FormattedArgumentNode $node);

    /**
     * @return TReturn
     */
    public function visitSelect(SelectNode $node);

    /**
     * @return TReturn
     */
    public function visitPlural(PluralNode $node);

    /**
     * @return TReturn
     */
    public function visitSelectOrdinal(SelectOrdinalNode $node);

    /**
     * @return TReturn
     */
    public function visitOption(OptionNode $node);

    /**
     * @return TReturn
     */
    public function visitPound(PoundNode $node);

    /**
     * @return TReturn
     */
    public function visitSpellout(SpelloutNode $node);

    /**
     * @return TReturn
     */
    public function visitOrdinal(OrdinalNode $node);

    /**
     * @return TReturn
     */
    public function visitDuration(DurationNode $node);

    /**
     * @return TReturn
     */
    public function visitChoice(ChoiceNode $node);
}
