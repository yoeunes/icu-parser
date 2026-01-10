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

namespace IcuParser\Tests\Loader;

use IcuParser\Loader\TranslationExtraction;
use PHPUnit\Framework\TestCase;

final class TranslationExtractionTest extends TestCase
{
    public function test_creates_extraction_with_messages(): void
    {
        $extraction = new TranslationExtraction(
            messages: [
                'app.hello' => 'Hello {name}',
                'app.goodbye' => 'Goodbye {name}',
            ],
            lines: [
                'app.hello' => 10,
                'app.goodbye' => 20,
            ],
        );

        $this->assertCount(2, $extraction->messages);
        $this->assertSame('Hello {name}', $extraction->messages['app.hello']);
        $this->assertSame('Goodbye {name}', $extraction->messages['app.goodbye']);
        $this->assertSame(10, $extraction->lines['app.hello']);
        $this->assertSame(20, $extraction->lines['app.goodbye']);
    }

    public function test_creates_extraction_with_default_lines_array(): void
    {
        $extraction = new TranslationExtraction(
            messages: ['app.hello' => 'Hello'],
        );

        $this->assertSame([], $extraction->lines);
    }

    public function test_creates_extraction_with_empty_messages(): void
    {
        $extraction = new TranslationExtraction(
            messages: [],
            lines: [],
        );

        $this->assertSame([], $extraction->messages);
        $this->assertSame([], $extraction->lines);
    }

    public function test_creates_extraction_with_messages_without_lines(): void
    {
        $extraction = new TranslationExtraction(
            messages: ['app.hello' => 'Hello', 'app.world' => 'World'],
        );

        $this->assertCount(2, $extraction->messages);
        $this->assertArrayNotHasKey('app.hello', $extraction->lines);
        $this->assertArrayNotHasKey('app.world', $extraction->lines);
    }

    public function test_is_readonly(): void
    {
        $extraction = new TranslationExtraction(
            messages: ['app.hello' => 'Hello'],
        );

        $this->assertObjectNotHasProperty('messages', new \ReflectionClass($extraction));
    }
}
