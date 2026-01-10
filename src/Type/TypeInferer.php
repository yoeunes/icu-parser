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

namespace IcuParser\Type;

use IcuParser\Node\ChoiceNode;
use IcuParser\Node\DurationNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\OrdinalNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\SpelloutNode;
use IcuParser\NodeVisitor\AbstractNodeVisitor;

/**
 * Infers parameter types from the AST.
 */
final class TypeInferer extends AbstractNodeVisitor
{
    private TypeMap $types;

    public function infer(MessageNode $message): TypeMap
    {
        $this->types = new TypeMap();
        $message->accept($this);

        return $this->types;
    }

    public function visitSimpleArgument(SimpleArgumentNode $node): void
    {
        $this->types->add($node->name, ParameterType::STRING);
    }

    public function visitFormattedArgument(FormattedArgumentNode $node): void
    {
        $this->types->add($node->name, $this->mapFormat($node->format));
    }

    public function visitSelect(SelectNode $node): void
    {
        $this->types->add($node->name, ParameterType::STRING);

        parent::visitSelect($node);
    }

    public function visitPlural(PluralNode $node): void
    {
        $this->types->add($node->name, ParameterType::NUMBER);

        parent::visitPlural($node);
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node): void
    {
        $this->types->add($node->name, ParameterType::NUMBER);

        parent::visitSelectOrdinal($node);
    }

    public function visitChoice(ChoiceNode $node): void
    {
        $this->types->add($node->name, ParameterType::NUMBER);

        parent::visitChoice($node);
    }

    public function visitSpellout(SpelloutNode $node): void
    {
        $this->visitFormattedArgument($node);
    }

    public function visitOrdinal(OrdinalNode $node): void
    {
        $this->visitFormattedArgument($node);
    }

    public function visitDuration(DurationNode $node): void
    {
        $this->visitFormattedArgument($node);
    }

    private function mapFormat(string $format): ParameterType
    {
        return match (strtolower($format)) {
            'number', 'spellout', 'ordinal', 'duration' => ParameterType::NUMBER,
            'date', 'time' => ParameterType::DATETIME,
            default => ParameterType::STRING,
        };
    }
}
