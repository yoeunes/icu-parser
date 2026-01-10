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
 * HTML highlighter visitor using CSS classes.
 */
final class HtmlHighlighterVisitor extends HighlighterVisitor
{
    private const CSS_CLASSES = [
        'brace' => 'icu-brace',
        'punctuation' => 'icu-punctuation',
        'argument' => 'icu-argument',
        'type' => 'icu-type',
        'keyword' => 'icu-keyword',
        'selector' => 'icu-selector',
        'number' => 'icu-number',
        'option' => 'icu-option',
        'string' => 'icu-string',
        'style' => 'icu-style',
        'text' => 'icu-text',
    ];

    public function __construct(
        private string $wrapper = 'span',
    ) {}

    protected function wrap(string $content, string $type): string
    {
        if ('' === $content) {
            return $content;
        }

        $class = self::CSS_CLASSES[$type] ?? null;
        if (null === $class || 'whitespace' === $type) {
            return $content;
        }

        return '<'.$this->wrapper.' class="'.$this->escape($class).'">'
            .$this->escapeHtml($content)
            .'</'.$this->wrapper.'>';
    }

    protected function escape(string $string): string
    {
        return $string;
    }

    private function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
