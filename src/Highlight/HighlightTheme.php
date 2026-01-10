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

namespace IcuParser\Highlight;

use IcuParser\Lexer\TokenType;

/**
 * Defines styling for highlighted ICU tokens.
 */
final readonly class HighlightTheme
{
    public function __construct(
        public string $brace = '',
        public string $punctuation = '',
        public string $identifier = '',
        public string $number = '',
        public string $text = '',
        public string $whitespace = '',
        public string $reset = '',
    ) {}

    public static function ansi(): self
    {
        return new self(
            brace: "\033[36m",
            punctuation: "\033[33m",
            identifier: "\033[32m",
            number: "\033[34m",
            text: '',
            whitespace: '',
            reset: "\033[0m",
        );
    }

    public static function plain(): self
    {
        return new self();
    }

    public function wrap(TokenType $type, string $value): string
    {
        $style = match ($type) {
            TokenType::T_LBRACE, TokenType::T_RBRACE => $this->brace,
            TokenType::T_COMMA,
            TokenType::T_COLON,
            TokenType::T_HASH,
            TokenType::T_EQUAL,
            TokenType::T_PIPE,
            TokenType::T_LT => $this->punctuation,
            TokenType::T_IDENTIFIER => $this->identifier,
            TokenType::T_NUMBER => $this->number,
            TokenType::T_TEXT => $this->text,
            TokenType::T_WHITESPACE => $this->whitespace,
            TokenType::T_EOF => '',
        };

        if ('' === $style || '' === $value) {
            return $value;
        }

        return $style.$value.$this->reset;
    }
}
