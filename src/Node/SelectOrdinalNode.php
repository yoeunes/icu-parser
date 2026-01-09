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
 * Select-ordinal argument.
 */
final readonly class SelectOrdinalNode extends ArgumentNode
{
    /**
     * @param array<int, OptionNode> $options
     */
    public function __construct(
        string $name,
        public array $options,
        public int|float|null $offset,
        int $startPosition,
        int $endPosition,
    ) {
        parent::__construct($name, $startPosition, $endPosition);
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visitSelectOrdinal($this);
    }
}
