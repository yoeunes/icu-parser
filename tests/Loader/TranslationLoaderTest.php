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

use IcuParser\Loader\TranslationLoader;
use IcuParser\Tests\Support\FilesystemTestCase;

final class TranslationLoaderTest extends FilesystemTestCase
{
    public function test_load_catalog_with_empty_paths(): void
    {
        $loader = new TranslationLoader([]);

        $catalog = $loader->loadCatalog();

        $this->assertEmpty($catalog->getLocales());
    }

    public function test_load_catalog_with_yaml_files(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello {name}'\n");
        $this->writeFile('messages.fr.yaml', "app:\n  hello: 'Bonjour {name}'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertSame(['messages'], $catalog->getDomains('en'));
        $this->assertSame('Hello {name}', $catalog->getMessage('app.hello', 'en', 'messages'));
        $this->assertSame('Bonjour {name}', $catalog->getMessage('app.hello', 'fr', 'messages'));
    }

    public function test_scan_returns_entries(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello {name}'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $entries = iterator_to_array($loader->scan());

        $this->assertCount(1, $entries);
        $this->assertSame('messages', $entries[0]->domain);
        $this->assertSame('app.hello', $entries[0]->id);
        $this->assertSame('en', $entries[0]->locale);
        $this->assertSame('Hello {name}', $entries[0]->message);
    }
}
