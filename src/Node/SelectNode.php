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
 * Select argument.
 */
final readonly class SelectNode extends ArgumentNode
{
    /**
     * @param array<OptionNode> $options
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
        return $visitor->visitSelect($this);
    }
}
