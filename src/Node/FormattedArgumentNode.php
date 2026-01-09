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
 * Argument with a formatter type and optional style.
 */
final readonly class FormattedArgumentNode extends ArgumentNode
{
    public function __construct(
        string $name,
        public string $format,
        public ?string $style,
        int $startPosition,
        int $endPosition,
    ) {
        parent::__construct($name, $startPosition, $endPosition);
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visitFormattedArgument($this);
    }
}
