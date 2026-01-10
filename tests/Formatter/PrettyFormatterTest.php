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

namespace IcuParser\Tests\Formatter;

use IcuParser\Formatter\PrettyFormatter;
use IcuParser\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class PrettyFormatterTest extends TestCase
{
    public function test_formats_select_message_with_alignment(): void
    {
        $message = '{organizer_gender, select, female {{organizer_name} has invited you to her party!} male {{organizer_name} has invited you to his party!} multiple {{organizer_name} have invited you to their party!} other {{organizer_name} has invited you to their party!}}';
        $ast = (new Parser())->parse($message);

        $formatted = (new PrettyFormatter())->format($ast);

        $expected = "{organizer_gender, select,\n"
            ."    female   {{organizer_name} has invited you to her party!}\n"
            ."    male     {{organizer_name} has invited you to his party!}\n"
            ."    multiple {{organizer_name} have invited you to their party!}\n"
            ."    other    {{organizer_name} has invited you to their party!}\n"
            .'}';

        $this->assertSame($expected, $formatted);
    }

    public function test_formats_plural_with_offset_and_literal_hash(): void
    {
        $message = "{count, plural, offset:1 one {# item} other {'#' items}}";
        $ast = (new Parser())->parse($message);

        $formatted = (new PrettyFormatter())->format($ast);

        $expected = "{count, plural, offset:1\n"
            ."    one   {# item}\n"
            ."    other {'#' items}\n"
            .'}';

        $this->assertSame($expected, $formatted);
    }

    public function test_formats_choice_message(): void
    {
        $message = '{value, choice, 0#none|1#one|2<two}';
        $ast = (new Parser())->parse($message);

        $formatted = (new PrettyFormatter())->format($ast);

        $expected = "{value, choice,\n"
            ."    0#none\n"
            ."    | 1#one\n"
            ."    | 2<two\n"
            .'}';

        $this->assertSame($expected, $formatted);
    }
}
