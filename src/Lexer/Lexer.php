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

use IcuParser\Exception\LexerException;

/**
 * ICU MessageFormat lexer.
 */
final class Lexer
{
    private const SINGLE_CHAR_TOKENS = [
        '{' => TokenType::T_LBRACE,
        '}' => TokenType::T_RBRACE,
        ',' => TokenType::T_COMMA,
        ':' => TokenType::T_COLON,
        '#' => TokenType::T_HASH,
        '=' => TokenType::T_EQUAL,
    ];

    private const QUOTE_SPECIALS = [
        '{' => true,
        '}' => true,
        '#' => true,
        '\'' => true,
    ];

    public function tokenize(string $message): TokenStream
    {
        if (!preg_match('//u', $message)) {
            throw LexerException::withContext('Input string is not valid UTF-8.', 0, $message);
        }

        $tokens = [];
        $length = \strlen($message);
        $position = 0;

        while ($position < $length) {
            $char = $message[$position];

            if ('\'' === $char) {
                $token = $this->readQuotedOrLiteral($message, $position, $length);
                $tokens[] = $token;
                $position += $token->length;

                continue;
            }

            if (isset(self::SINGLE_CHAR_TOKENS[$char])) {
                $tokens[] = new Token(self::SINGLE_CHAR_TOKENS[$char], $char, $position, 1);
                $position++;

                continue;
            }

            if (ctype_space($char)) {
                $start = $position;
                $position++;
                while ($position < $length && ctype_space($message[$position])) {
                    $position++;
                }
                $value = substr($message, $start, $position - $start);
                $tokens[] = new Token(TokenType::T_WHITESPACE, $value, $start, $position - $start);

                continue;
            }

            if ($this->isIdentifierStart($char)) {
                $start = $position;
                $position++;
                while ($position < $length && $this->isIdentifierPart($message[$position])) {
                    $position++;
                }
                $value = substr($message, $start, $position - $start);
                $tokens[] = new Token(TokenType::T_IDENTIFIER, $value, $start, $position - $start);

                continue;
            }

            if ($this->isNumberStart($message, $position, $length)) {
                $start = $position;
                $position++;
                while ($position < $length && ctype_digit($message[$position])) {
                    $position++;
                }
                if ($position < $length && '.' === $message[$position]) {
                    $position++;
                    while ($position < $length && ctype_digit($message[$position])) {
                        $position++;
                    }
                }
                $value = substr($message, $start, $position - $start);
                $tokens[] = new Token(TokenType::T_NUMBER, $value, $start, $position - $start);

                continue;
            }

            $start = $position;
            $position++;
            while ($position < $length && !$this->shouldBreakText($message, $position)) {
                $position++;
            }
            $value = substr($message, $start, $position - $start);
            $tokens[] = new Token(TokenType::T_TEXT, $value, $start, $position - $start);
        }

        $tokens[] = new Token(TokenType::T_EOF, '', $length, 0);

        return new TokenStream($tokens, $message);
    }

    private function readQuotedOrLiteral(string $message, int $position, int $length): Token
    {
        $next = $position + 1 < $length ? $message[$position + 1] : null;

        if ('\'' === $next) {
            return new Token(TokenType::T_TEXT, "'", $position, 2);
        }

        if (null !== $next && isset(self::QUOTE_SPECIALS[$next])) {
            return $this->readQuotedLiteral($message, $position, $length);
        }

        return new Token(TokenType::T_TEXT, "'", $position, 1);
    }

    private function readQuotedLiteral(string $message, int $start, int $length): Token
    {
        $position = $start + 1;
        $literal = '';

        while ($position < $length) {
            $char = $message[$position];

            if ('\'' === $char) {
                $next = $position + 1 < $length ? $message[$position + 1] : null;
                if ('\'' === $next) {
                    $literal .= "'";
                    $position += 2;

                    continue;
                }

                $position++;

                return new Token(TokenType::T_TEXT, $literal, $start, $position - $start);
            }

            $literal .= $char;
            $position++;
        }

        throw LexerException::withContext('Unterminated quoted literal.', $start, $message);
    }

    private function isIdentifierStart(string $char): bool
    {
        return ('_' === $char) || (ctype_alpha($char) && 1 === \strlen($char));
    }

    private function isIdentifierPart(string $char): bool
    {
        return ('_' === $char || '-' === $char) || (ctype_alnum($char) && 1 === \strlen($char));
    }

    private function isNumberStart(string $message, int $position, int $length): bool
    {
        $char = $message[$position];
        if (ctype_digit($char)) {
            return true;
        }

        if ('-' !== $char) {
            return false;
        }

        return $position + 1 < $length && ctype_digit($message[$position + 1]);
    }

    private function shouldBreakText(string $message, int $position): bool
    {
        $char = $message[$position];

        return '\'' === $char
            || isset(self::SINGLE_CHAR_TOKENS[$char])
            || ctype_space($char)
            || $this->isIdentifierStart($char)
            || $this->isNumberStart($message, $position, \strlen($message));
    }
}
