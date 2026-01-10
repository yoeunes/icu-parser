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

namespace IcuParser\Tests\Node;

use IcuParser\Node\TextNode;
use PHPUnit\Framework\TestCase;

final class AbstractNodeTest extends TestCase
{
    public function test_positions_are_exposed(): void
    {
        $node = new TextNode('hello', 2, 7);

        $this->assertSame(2, $node->getStartPosition());
        $this->assertSame(7, $node->getEndPosition());
    }
}
