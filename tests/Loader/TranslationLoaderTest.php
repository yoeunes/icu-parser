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

    public function test_load_catalog_with_multiple_locales(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello'\n");
        $this->writeFile('messages.fr.yaml', "app:\n  hello: 'Bonjour'\n");
        $this->writeFile('messages.es.yaml', "app:\n  hello: 'Hola'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertCount(3, $catalog->getLocales());
        $this->assertContains('en', $catalog->getLocales());
        $this->assertContains('fr', $catalog->getLocales());
        $this->assertContains('es', $catalog->getLocales());
    }

    public function test_load_catalog_with_xliff_files(): void
    {
        $xlfContent = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="app.hello">
        <source>Hello</source>
        <target>Hello {name}</target>
      </trans-unit>
    </body>
  </file>
</xliff>';
        $this->writeFile('messages.en.xlf', $xlfContent);

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertSame(['messages'], $catalog->getDomains('en'));
        $this->assertSame('Hello {name}', $catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_load_catalog_with_default_locale(): void
    {
        $this->writeFile('messages.yaml', "app:\n  hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir()], 'en');

        $catalog = $loader->loadCatalog();

        $this->assertSame(['messages'], $catalog->getDomains('en'));
        $this->assertSame('Hello', $catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_load_catalog_with_custom_domain(): void
    {
        $this->writeFile('custom.en.yaml', "app:\n  hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir()], null, null, defaultDomain: 'custom');

        $catalog = $loader->loadCatalog();

        $this->assertSame(['custom'], $catalog->getDomains('en'));
        $this->assertSame('Hello', $catalog->getMessage('app.hello', 'en', 'custom'));
    }

    public function test_load_catalog_with_multiple_domains(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello'\n");
        $this->writeFile('errors.en.yaml', "app:\n  notfound: 'Not Found'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertCount(2, $catalog->getDomains('en'));
        $this->assertContains('messages', $catalog->getDomains('en'));
        $this->assertContains('errors', $catalog->getDomains('en'));
    }

    public function test_scan_with_multiple_files(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello'\n");
        $this->writeFile('messages.fr.yaml', "app:\n  hello: 'Bonjour'\n");
        $this->writeFile('errors.en.yaml', "app:\n  error: 'Error'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $entries = iterator_to_array($loader->scan());

        $this->assertCount(3, $entries);
    }

    public function test_scan_with_xliff_files(): void
    {
        $xlfContent = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="app.hello">
        <source>Hello</source>
        <target>Hello {name}</target>
      </trans-unit>
    </body>
  </file>
</xliff>';
        $this->writeFile('messages.en.xlf', $xlfContent);

        $loader = new TranslationLoader([$this->createTempDir()]);

        $entries = iterator_to_array($loader->scan());

        $this->assertCount(1, $entries);
        $this->assertSame('Hello {name}', $entries[0]->message);
    }

    public function test_normalize_domain_with_intl_icu_suffix(): void
    {
        $this->writeFile('app+intl-icu.en.yaml', "hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertSame('Hello', $catalog->getMessage('hello', 'en', 'app'));
    }

    public function test_normalize_paths_with_trailing_slash(): void
    {
        $loader = new TranslationLoader([$this->createTempDir().'/']);

        $catalog = $loader->loadCatalog();

        $this->assertIsObject($catalog);
    }

    public function test_normalize_paths_with_duplicates(): void
    {
        $loader = new TranslationLoader([$this->createTempDir(), $this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertIsObject($catalog);
    }

    public function test_normalize_paths_with_empty_string(): void
    {
        $loader = new TranslationLoader(['', $this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertIsObject($catalog);
    }

    public function test_resolve_locale_domain_from_filename(): void
    {
        $this->writeFile('app.en.yaml', "hello: 'Hello'\n");
        $this->writeFile('app.fr.yaml', "hello: 'Bonjour'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertSame('Hello', $catalog->getMessage('hello', 'en', 'app'));
        $this->assertSame('Bonjour', $catalog->getMessage('hello', 'fr', 'app'));
    }

    public function test_resolve_domain_with_dots_in_name(): void
    {
        $this->writeFile('my.app.en.yaml', "hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertSame('Hello', $catalog->getMessage('hello', 'en', 'my.app'));
    }

    public function test_catalog_returns_cached_instance(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog1 = $loader->loadCatalog();
        $catalog2 = $loader->loadCatalog();

        $this->assertSame($catalog1, $catalog2);
    }

    public function test_scan_with_empty_directory(): void
    {
        $loader = new TranslationLoader([$this->createTempDir()]);

        $entries = iterator_to_array($loader->scan());

        $this->assertCount(0, $entries);
    }

    public function test_load_catalog_with_file_instead_of_directory(): void
    {
        $this->writeFile('messages.en.yaml', "app:\n  hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir().'/messages.en.yaml']);

        $catalog = $loader->loadCatalog();

        $this->assertSame('Hello', $catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_load_catalog_with_nested_directory(): void
    {
        $nestedDir = $this->createTempDir().'/nested';
        mkdir($nestedDir);
        $this->writeFile('nested/messages.en.yaml', "app:\n  hello: 'Hello'\n");

        $loader = new TranslationLoader([$this->createTempDir()]);

        $catalog = $loader->loadCatalog();

        $this->assertSame('Hello', $catalog->getMessage('app.hello', 'en', 'messages'));
    }
}
