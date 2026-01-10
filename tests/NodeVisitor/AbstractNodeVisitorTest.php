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

namespace IcuParser\Tests\NodeVisitor;

use IcuParser\Node\DurationNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\OrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\SpelloutNode;
use IcuParser\Node\TextNode;
use IcuParser\NodeVisitor\AbstractNodeVisitor;
use PHPUnit\Framework\TestCase;

final class AbstractNodeVisitorTest extends TestCase
{
    public function test_visits_leaf_nodes(): void
    {
        $visitor = new class extends AbstractNodeVisitor {};

        $nodes = [
            new TextNode('hi', 0, 2),
            new SimpleArgumentNode('name', 0, 6),
            new FormattedArgumentNode('count', 'number', null, 0, 10),
            new SpelloutNode('count', 'spellout', null, 0, 10),
            new OrdinalNode('rank', 'ordinal', null, 0, 10),
            new DurationNode('elapsed', 'duration', null, 0, 10),
        ];

        foreach ($nodes as $node) {
            $node->accept($visitor);
        }

        $this->assertCount(6, $nodes);
    }
}
