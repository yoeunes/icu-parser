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
 * Token stream for efficient parsing with direct indexing.
 */
final class TokenStream
{
    private int $position = 0;

    private int $maxPosition = 0;

    /**
     * @param array<Token> $tokens
     */
    public function __construct(
        private readonly array $tokens,
        private readonly string $source,
    ) {
        $this->maxPosition = \count($this->tokens) - 1;
    }

    public function current(): Token
    {
        if ($this->position > $this->maxPosition) {
            return new Token(TokenType::T_EOF, '', $this->position, 0);
        }

        return $this->tokens[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function peek(int $offset = 1): Token
    {
        $target = $this->position + $offset;

        if ($target < 0 || $target > $this->maxPosition) {
            return new Token(TokenType::T_EOF, '', $target, 0);
        }

        return $this->tokens[$target];
    }

    public function isAtEnd(): bool
    {
        return $this->position > $this->maxPosition
            || TokenType::T_EOF === $this->tokens[$this->position]->type;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        if ($position < 0 || $position > $this->maxPosition + 1) {
            throw new \RuntimeException(sprintf('Position %d is out of bounds.', $position));
        }

        $this->position = $position;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSourceLength(): int
    {
        return \strlen($this->source);
    }

    /**
     * @return array<Token>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }
}
