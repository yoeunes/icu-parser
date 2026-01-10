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
 * Choice format node (deprecated in ICU 4.4+, but part of spec).
 *
 * Syntax: {value, choice, limit#option|limit#option|limit<option}
 */
final readonly class ChoiceNode extends ArgumentNode
{
    /**
     * @param array<int, ChoiceOptionNode> $options
     */
    public function __construct(
        string $name,
        public array $options,
        int $startPosition,
        int $endPosition,
    ) {
        parent::__construct($name, $startPosition, $endPosition);
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visitChoice($this);
    }
}
