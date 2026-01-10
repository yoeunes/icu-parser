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

namespace IcuParser\NodeVisitor;

/**
 * Console highlighter visitor using ANSI color codes.
 */
final class ConsoleHighlighterVisitor extends HighlighterVisitor
{
    private const RESET = "\033[0m";
    private const BRACE = "\033[36m";      // Cyan
    private const PUNCTUATION = "\033[33m"; // Yellow
    private const ARGUMENT = "\033[32m";    // Green
    private const TYPE = "\033[35m";        // Magenta
    private const KEYWORD = "\033[35m";     // Magenta
    private const SELECTOR = "\033[33m";    // Yellow
    private const NUMBER = "\033[34m";      // Blue
    private const OPTION = "\033[36m";      // Cyan
    private const STRING = "\033[90m";      // Gray
    private const TEXT = '';                // No color (plain)
    private const STYLE = "\033[90m";       // Gray

    public function __construct(
        private bool $ansi = true,
    ) {}

    protected function wrap(string $content, string $type): string
    {
        if (!$this->ansi) {
            return $content;
        }

        $color = match ($type) {
            'brace' => self::BRACE,
            'punctuation' => self::PUNCTUATION,
            'argument' => self::ARGUMENT,
            'type' => self::TYPE,
            'keyword' => self::KEYWORD,
            'selector' => self::SELECTOR,
            'number' => self::NUMBER,
            'option' => self::OPTION,
            'string' => self::STRING,
            'style' => self::STYLE,
            'whitespace' => '',
            'text' => self::TEXT,
            default => '',
        };

        if ('' === $color || '' === $content) {
            return $content;
        }

        return $color.$content.self::RESET;
    }

    protected function escape(string $string): string
    {
        return $string;
    }
}
