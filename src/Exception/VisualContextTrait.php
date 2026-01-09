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

namespace IcuParser\Exception;

/**
 * Provides a compact visual excerpt for parser errors.
 */
trait VisualContextTrait
{
    private const MAX_CONTEXT_WIDTH = 80;

    private ?string $source = null;

    private string $visualSnippet = '';

    public function getMessageSource(): ?string
    {
        return $this->source;
    }

    public function getVisualSnippet(): string
    {
        return $this->visualSnippet;
    }

    private function initializeContext(?int $position, ?string $source): void
    {
        $this->source = $source;
        $this->visualSnippet = $this->buildVisualSnippet($position, $source);
    }

    private function buildVisualSnippet(?int $position, ?string $source): string
    {
        if (null === $source || null === $position || $position < 0) {
            return '';
        }

        $length = \strlen($source);
        $caretIndex = $position > $length ? $length : $position;

        $lineStart = strrpos($source, "\n", $caretIndex - $length);
        $lineStart = false === $lineStart ? 0 : $lineStart + 1;
        $lineEnd = strpos($source, "\n", $caretIndex);
        $lineEnd = false === $lineEnd ? $length : $lineEnd;

        $lineNumber = substr_count($source, "\n", 0, $lineStart) + 1;

        $displayStart = $lineStart;
        $displayEnd = $lineEnd;

        if (($displayEnd - $displayStart) > self::MAX_CONTEXT_WIDTH) {
            $half = intdiv(self::MAX_CONTEXT_WIDTH, 2);
            $displayStart = max($lineStart, $caretIndex - $half);
            $displayEnd = min($lineEnd, $displayStart + self::MAX_CONTEXT_WIDTH);

            if (($displayEnd - $displayStart) > self::MAX_CONTEXT_WIDTH) {
                $displayStart = $displayEnd - self::MAX_CONTEXT_WIDTH;
            }
        }

        $prefixEllipsis = $displayStart > $lineStart ? '...' : '';
        $suffixEllipsis = $displayEnd < $lineEnd ? '...' : '';

        $excerpt = $prefixEllipsis
            .substr($source, $displayStart, $displayEnd - $displayStart)
            .$suffixEllipsis;

        $caretOffset = ('' === $prefixEllipsis ? 0 : 3) + ($caretIndex - $displayStart);
        if ($caretOffset < 0) {
            $caretOffset = 0;
        }

        $lineLabel = 'Line '.$lineNumber.': ';

        return $lineLabel.$excerpt."\n"
            .str_repeat(' ', \strlen($lineLabel) + $caretOffset).'^';
    }
}
