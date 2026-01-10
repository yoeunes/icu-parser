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

use IcuParser\Node\MessageNode;
use IcuParser\NodeVisitor\ConsoleHighlighterVisitor;
use IcuParser\Parser\Parser;

/**
 * Produces a highlighted ICU message string using AST traversal.
 */
final readonly class Highlighter
{
    public function __construct(private Parser $parser = new Parser()) {}

    public function highlight(string $message, ?HighlightTheme $theme = null): string
    {
        $theme ??= HighlightTheme::ansi();
        $ast = $this->parser->parse($message);

        // Map theme colors to visitor
        $ansi = '' !== $theme->brace || '' !== $theme->punctuation;
        $visitor = new ConsoleHighlighterVisitor($ansi);

        // Use the visitor for highlighting
        $highlighted = $ast->accept($visitor);

        // For legacy theme support, if a custom theme is provided, apply its colors
        if (!$this->isDefaultTheme($theme)) {
            return $this->applyCustomTheme($highlighted, $theme);
        }

        return $highlighted;
    }

    public function highlightFromAst(MessageNode $ast, bool $ansi = true): string
    {
        $visitor = new ConsoleHighlighterVisitor($ansi);

        return $ast->accept($visitor);
    }

    private function isDefaultTheme(HighlightTheme $theme): bool
    {
        $default = HighlightTheme::ansi();

        return $theme->brace === $default->brace
            && $theme->punctuation === $default->punctuation
            && $theme->identifier === $default->identifier
            && $theme->number === $default->number
            && $theme->text === $default->text
            && $theme->whitespace === $default->whitespace
            && $theme->reset === $default->reset;
    }

    private function applyCustomTheme(string $highlighted, HighlightTheme $theme): string
    {
        // This is a simplified approach - for full theme support,
        // you would need to parse the ANSI codes and replace them
        // with custom theme colors
        return strtr($highlighted, [
            "\033[36m" => $theme->brace,       // Cyan -> brace color
            "\033[33m" => $theme->punctuation, // Yellow -> punctuation color
            "\033[32m" => $theme->identifier,  // Green -> identifier color
            "\033[34m" => $theme->number,      // Blue -> number color
            "\033[35m" => $theme->identifier,  // Magenta -> identifier color
            "\033[90m" => $theme->text,        // Gray -> text color
            "\033[0m" => $theme->reset,
        ]);
    }
}
