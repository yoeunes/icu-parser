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

use IcuParser\Lexer\Lexer;
use IcuParser\Lexer\TokenType;

/**
 * Produces a highlighted ICU message string using lexer tokens.
 */
final readonly class Highlighter
{
    public function __construct(private Lexer $lexer = new Lexer()) {}

    public function highlight(string $message, ?HighlightTheme $theme = null): string
    {
        $theme ??= HighlightTheme::ansi();
        $stream = $this->lexer->tokenize($message);
        $source = $stream->getSource();
        $output = '';

        foreach ($stream->getTokens() as $token) {
            if (TokenType::T_EOF === $token->type) {
                break;
            }

            $chunk = substr($source, $token->position, $token->length);
            if ('' === $chunk) {
                continue;
            }

            $output .= $theme->wrap($token->type, $chunk);
        }

        return $output;
    }
}
