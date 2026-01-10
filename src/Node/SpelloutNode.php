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

namespace IcuParser\Node;

use IcuParser\NodeVisitor\NodeVisitorInterface;

/**
 * Spellout number format node.
 */
final readonly class SpelloutNode extends FormattedArgumentNode
{
    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visitSpellout($this);
    }
}
