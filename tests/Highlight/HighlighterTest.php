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

namespace IcuParser\Tests\Highlight;

use IcuParser\Highlight\Highlighter;
use IcuParser\Highlight\HighlightTheme;
use PHPUnit\Framework\TestCase;

final class HighlighterTest extends TestCase
{
    public function test_highlight_with_plain_theme_returns_original(): void
    {
        $highlighter = new Highlighter();
        $message = 'Hello {name}';

        $result = $highlighter->highlight($message, HighlightTheme::plain());

        $this->assertSame($message, $result);
    }

    public function test_highlight_with_ansi_theme(): void
    {
        $highlighter = new Highlighter();
        $message = '{0, number}';

        $result = $highlighter->highlight($message, HighlightTheme::ansi());

        $expected = "\033[36m{\033[0m"
            ."\033[32m0\033[0m"
            ."\033[33m,\033[0m"
            .' '
            ."\033[35mnumber\033[0m"
            ."\033[36m}\033[0m";

        $this->assertSame($expected, $result);
    }
}
