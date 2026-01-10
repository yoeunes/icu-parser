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

/**
 * Choice option node.
 */
final readonly class ChoiceOptionNode
{
    public function __construct(
        public float $limit,
        public bool $isExclusive,
        public MessageNode $message,
        public int $startPosition,
        public int $endPosition,
    ) {}
}
