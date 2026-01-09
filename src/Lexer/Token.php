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

namespace IcuParser\Lexer;

/**
 * Represents a single token emitted by the lexer.
 */
final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
        public int $position,
        public int $length,
    ) {}

    public function getEndPosition(): int
    {
        return $this->position + $this->length;
    }
}
