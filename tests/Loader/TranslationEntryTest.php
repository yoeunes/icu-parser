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

use IcuParser\Loader\TranslationEntry;
use PHPUnit\Framework\TestCase;

final class TranslationEntryTest extends TestCase
{
    public function test_creates_translation_entry_with_all_properties(): void
    {
        $entry = new TranslationEntry(
            file: '/path/to/file.yaml',
            locale: 'en',
            domain: 'messages',
            id: 'app.hello',
            message: 'Hello {name}',
            line: 10,
        );

        $this->assertSame('/path/to/file.yaml', $entry->file);
        $this->assertSame('en', $entry->locale);
        $this->assertSame('messages', $entry->domain);
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello {name}', $entry->message);
        $this->assertSame(10, $entry->line);
    }

    public function test_creates_translation_entry_with_null_line(): void
    {
        $entry = new TranslationEntry(
            file: '/path/to/file.yaml',
            locale: 'en',
            domain: 'messages',
            id: 'app.hello',
            message: 'Hello {name}',
            line: null,
        );

        $this->assertNull($entry->line);
    }

    public function test_is_readonly(): void
    {
        $entry = new TranslationEntry(
            file: '/path/to/file.yaml',
            locale: 'en',
            domain: 'messages',
            id: 'app.hello',
            message: 'Hello {name}',
            line: 10,
        );

        $this->assertObjectNotHasProperty('file', new \ReflectionClass($entry));
    }
}
