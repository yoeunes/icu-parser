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

namespace IcuParser\Tests\Catalog;

use IcuParser\Catalog\CatalogEntry;
use IcuParser\Catalog\CatalogInterface;
use IcuParser\Loader\TranslationLoader;
use IcuParser\Tests\Support\FilesystemTestCase;

final class CatalogTest extends FilesystemTestCase
{
    public function test_loads_entries_with_metadata(): void
    {
        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello {name}\"\n");

        $catalog = $this->createCatalog();
        $entry = $catalog->getEntry('app.hello', 'en');

        $this->assertInstanceOf(CatalogEntry::class, $entry);
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello {name}', $entry->message);
        $this->assertSame(2, $entry->line);
        $this->assertStringContainsString('translations/messages.en.yaml', $entry->file);
    }

    public function test_returns_entries_per_locale(): void
    {
        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello\"\n");
        $this->writeFile('translations/messages.fr.yaml', "app:\n  hello: \"Bonjour\"\n");

        $catalog = $this->createCatalog();
        $entries = $catalog->getEntries('fr', 'messages');

        $this->assertCount(1, $entries);
        $this->assertArrayHasKey('app.hello', $entries);
        $this->assertSame('Bonjour', $entries['app.hello']->message);
    }

    private function createCatalog(): CatalogInterface
    {
        $loader = new TranslationLoader([$this->createTempDir()], 'en');

        return $loader->loadCatalog();
    }
}
