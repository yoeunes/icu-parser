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
 * Selector option for select and plural arguments.
 */
final readonly class OptionNode extends AbstractNode
{
    public function __construct(
        public string $selector,
        public MessageNode $message,
        int $startPosition,
        int $endPosition,
        public bool $explicit = false,
        public int|float|null $explicitValue = null,
    ) {
        parent::__construct($startPosition, $endPosition);
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visitOption($this);
    }
}
