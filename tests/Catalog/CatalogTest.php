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

use IcuParser\Catalog\Cache\CatalogCacheInterface;
use IcuParser\Catalog\Catalog;
use IcuParser\Catalog\CatalogEntry;
use IcuParser\Loader\TranslationExtractorInterface;
use IcuParser\Tests\Support\FilesystemTestCase;

final class CatalogTest extends FilesystemTestCase
{
    private Catalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = new Catalog(
            ['en' => ['messages' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'en',
        );
    }

    public function test_get_message_with_id_and_default_locale_and_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_get_message_with_custom_locale_uses_default_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'en', 'messages'));
        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_get_message_with_default_locale_uses_custom_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'en', 'messages'));
        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_get_message_with_id_and_custom_locale_uses_custom_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'custom' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'custom', 'messages'));
        $this->assertSame('Hello World', $this->catalog->getMessage('app.hello', 'custom', 'messages'));
    }

    public function test_get_entry_with_id_and_default_locale_and_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $entry = $this->catalog->getEntry('app.hello', 'en', 'messages');
        $this->assertInstanceOf(CatalogEntry::class, $entry);
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello World', $entry->message);
        $this->assertSame(5, $entry->line);
        $this->assertStringContainsString('test.yaml', $entry->file);
    }

    public function test_get_entry_with_id_and_custom_locale_uses_default_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $entry = $this->catalog->getEntry('app.hello', 'en', 'messages');
        $this->assertInstanceOf(CatalogEntry::class, $entry);
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello World', $entry->message);
        $this->assertSame(5, $entry->line);
        $this->assertStringContainsString('test.yaml', $entry->file);
    }

    public function test_get_entry_with_id_and_custom_locale_uses_custom_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'custom' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $entry = $this->catalog->getEntry('app.hello', 'custom', 'messages');
        $this->assertInstanceOf(CatalogEntry::class, $entry);
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello World', $entry->message);
        $this->assertSame(5, $entry->line);
        $this->assertStringContainsString('test.yaml', $entry->file);
    }

    public function test_has_with_nonexistent_id_returns_false(): void
    {
        $this->assertFalse($this->catalog->has('nonexistent'));
    }

    public function test_has_with_empty_id_returns_false(): void
    {
        $this->assertFalse($this->catalog->has(''));
    }

    public function test_has_with_null_locale_fallbacks_to_default(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($this->catalog->has('app.hello', null, 'messages'));
    }

    public function test_has_with_null_domain_fallbacks_to_default(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($this->catalog->has('app.hello', 'en', null));
    }

    public function test_get_locales_returns_single_locale(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $locales = $this->catalog->getLocales();
        $this->assertCount(1, $locales);
        $this->assertContains('en', $locales);
    }

    public function test_get_locales_returns_multiple_locales(): void
    {
        $catalog = new Catalog(
            ['en' => ['messages' => ['dummy']], 'fr' => ['messages' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'en',
        );

        $this->setCatalogData($catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
            'fr' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Bonjour',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $locales = $catalog->getLocales();
        $this->assertCount(2, $locales);
        $this->assertContains('en', $locales);
        $this->assertContains('fr', $locales);
    }

    public function test_get_domains_returns_single_domain(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $domains = $this->catalog->getDomains('en');
        $this->assertCount(1, $domains);
        $this->assertContains('messages', $domains);
    }

    public function test_get_domains_returns_multiple_domains(): void
    {
        $catalog = new Catalog(
            ['en' => ['messages' => ['dummy'], 'admin' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'en',
        );

        $this->setCatalogData($catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
                'admin' => [
                    'admin.message' => [
                        'message' => 'Admin Only',
                        'file' => 'admin.yaml',
                        'line' => 10,
                    ],
                ],
            ],
        ]);

        $domains = $catalog->getDomains('en');
        $this->assertCount(2, $domains);
        $this->assertContains('messages', $domains);
        $this->assertContains('admin', $domains);
    }

    public function test_get_entries_returns_single_entry(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $entries = $this->catalog->getEntries('en', 'messages');
        $this->assertCount(1, $entries);
        $this->assertSame('app.hello', $entries['app.hello']->id);
    }

    public function test_get_entries_returns_multiple_entries(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                    'app.goodbye' => [
                        'message' => 'Goodbye',
                        'file' => 'test.yaml',
                        'line' => 15,
                    ],
                ],
            ],
        ]);

        $entries = $this->catalog->getEntries('en', 'messages');
        $this->assertCount(2, $entries);
        $this->assertArrayHasKey('app.hello', $entries);
        $this->assertArrayHasKey('app.goodbye', $entries);
    }

    public function test_get_entries_with_different_locales_returns_filtered_entries(): void
    {
        $catalog = new Catalog(
            ['en' => ['messages' => ['dummy']], 'fr' => ['messages' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'en',
        );

        $this->setCatalogData($catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
            'fr' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Bonjour',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $enEntries = $catalog->getEntries('en', 'messages');
        $frEntries = $catalog->getEntries('fr', 'messages');

        $this->assertCount(1, $enEntries);
        $this->assertSame('app.hello', $enEntries['app.hello']->id);
        $this->assertCount(1, $frEntries);
        $this->assertSame('Bonjour', $frEntries['app.hello']->message);
    }

    public function test_get_entries_with_nonexistent_locale_returns_empty(): void
    {
        $this->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $entries = $this->catalog->getEntries('nonexistent', 'messages');
        $this->assertSame([], $entries);
    }

    public function test_resolve_locale_with_valid_locale(): void
    {
        $reflection = new \ReflectionClass($this->catalog);
        $method = $reflection->getMethod('resolveLocale');

        $this->assertSame('en', $method->invoke($this->catalog, 'en'));
        $this->assertSame('custom', $method->invoke($this->catalog, 'custom'));
        $this->assertSame('en', $method->invoke($this->catalog, null));
    }

    public function test_resolve_locale_with_valid_locale_and_custom_fallback(): void
    {
        $catalog = new Catalog(
            ['custom' => ['messages' => ['dummy']], 'en' => ['messages' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'en',
        );

        $this->setCatalogData($catalog, [
            'custom' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('custom', $this->callPrivateMethod($catalog, 'resolveLocale', 'custom'));
        $this->assertSame('en', $this->callPrivateMethod($catalog, 'resolveLocale', 'en'));
    }

    public function test_resolve_locale_with_empty_locale_uses_default(): void
    {
        $this->assertSame('en', $this->callPrivateMethod($this->catalog, 'resolveLocale', ''));
        $this->assertSame('en', $this->callPrivateMethod($this->catalog, 'resolveLocale', null));
    }

    public function test_resolve_locale_with_null_locale_uses_custom_fallback(): void
    {
        $catalog = new Catalog(
            ['custom' => ['messages' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'custom',
        );

        $this->setCatalogData($catalog, [
            'custom' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('custom', $this->callPrivateMethod($catalog, 'resolveLocale', 'custom'));
        $this->assertSame('custom', $this->callPrivateMethod($catalog, 'resolveLocale', null));
    }

    public function test_resolve_domain_with_valid_domain(): void
    {
        $this->assertSame('custom', $this->callPrivateMethod($this->catalog, 'resolveDomain', 'custom'));
        $this->assertSame('messages', $this->callPrivateMethod($this->catalog, 'resolveDomain', null));
    }

    public function test_resolve_domain_with_empty_domain_uses_default(): void
    {
        $this->assertSame('messages', $this->callPrivateMethod($this->catalog, 'resolveDomain', null));
    }

    public function test_resolve_domain_with_empty_domain_uses_custom(): void
    {
        $this->setCatalogData($this->catalog, [
            'custom' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertSame('custom', $this->callPrivateMethod($this->catalog, 'resolveDomain', 'custom'));
        $this->assertSame('messages', $this->callPrivateMethod($this->catalog, 'resolveDomain', null));
    }

    public function test_ensure_loaded_is_called_when_cache_miss(): void
    {
        $this->expectNotToPerformAssertions();

        $catalog = new Catalog(
            [],
            $this->createMock(TranslationExtractorInterface::class),
            $this->createMock(CatalogCacheInterface::class),
            'test',
        );

        $this->callPrivateMethod($catalog, 'ensureLoaded', 'en', 'messages');
    }

    public function test_ensure_loaded_is_not_called_when_cache_hit(): void
    {
        $cache = $this->createMock(CatalogCacheInterface::class);
        $catalog = new Catalog(
            [],
            $this->createMock(TranslationExtractorInterface::class),
            $cache,
            'test',
        );

        // Cache hit
        $cache->expects($this->once())
            ->method('getLocaleMessages')
            ->willReturn(['app.hello' => ['message' => 'Hello', 'file' => 'test', 'line' => 1]]);
        $this->callPrivateMethod($catalog, 'ensureLoaded', 'en', 'messages');
    }

    public function test_ensure_loaded_with_null_cache_data(): void
    {
        $cache = $this->createMock(CatalogCacheInterface::class);
        $catalog = new Catalog(
            [],
            $this->createMock(TranslationExtractorInterface::class),
            $cache,
            'test',
        );

        // Cache returns data
        $cache->expects($this->once())
            ->method('getLocaleMessages')
            ->willReturn(['app.hello' => ['message' => 'Hello World', 'file' => 'test', 'line' => 1]]);
        $this->callPrivateMethod($catalog, 'ensureLoaded', 'en', 'messages');

        $this->assertSame('Hello World', $catalog->getMessage('app.hello', 'en', 'messages'));
    }

    public function test_ensure_loaded_with_cache_exception(): void
    {
        $cache = $this->createMock(CatalogCacheInterface::class);
        $catalog = new Catalog(
            [],
            $this->createMock(TranslationExtractorInterface::class),
            $cache,
            'test',
        );

        $cache->expects($this->once())
            ->method('getLocaleMessages')
            ->willThrowException(new \Exception('Cache error'));

        $this->expectException(\Exception::class);
        $this->callPrivateMethod($catalog, 'ensureLoaded', 'en', 'messages');
    }

    public function test_hydrate_entries_filters_by_locale_and_domain(): void
    {
        $catalog = new Catalog(
            ['en' => ['messages' => ['dummy'], 'admin' => ['dummy']], 'fr' => ['messages' => ['dummy']]],
            new DummyExtractor(),
            new DummyCache(),
            'test',
            'en',
        );

        $this->setCatalogData($catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
                'admin' => [
                    'app.admin' => [
                        'message' => 'Admin Only',
                        'file' => 'admin.yaml',
                        'line' => 8,
                    ],
                ],
            ],
            'fr' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Bonjour',
                        'file' => 'test.yaml',
                        'line' => 6,
                    ],
                ],
            ],
        ]);

        $this->assertSame(['app.hello'], array_keys($catalog->getEntries('en', 'messages')));
        $this->assertSame(['app.admin'], array_keys($catalog->getEntries('en', 'admin')));
        $this->assertSame(['app.hello'], array_keys($catalog->getEntries('fr', 'messages')));
    }

    protected function createTempDir(): string
    {
        return sys_get_temp_dir().'/icu_parser_test_'.uniqid().'/translations';
    }

    /**
     * @param array<string, array<string, array<string, array{message: string, file: string, line: int}>>> $data
     */
    private function setCatalogData(Catalog $catalog, array $data): void
    {
        $reflection = new \ReflectionClass($catalog);
        $messagesProperty = $reflection->getProperty('messages');
        $loadedProperty = $reflection->getProperty('loaded');

        /** @var array<string, array<string, array<string, CatalogEntry>>> $messages */
        $messages = $messagesProperty->getValue($catalog);
        /** @var array<string, array<string, bool>> $loaded */
        $loaded = $loadedProperty->getValue($catalog);

        foreach ($data as $locale => $localeData) {
            foreach ($localeData as $domain => $domainData) {
                foreach ($domainData as $id => $entryData) {
                    $messages[$locale][$domain][$id] = new CatalogEntry(
                        $id,
                        (string) $entryData['message'],
                        (string) $entryData['file'],
                        (int) $entryData['line'],
                    );
                }
                $loaded[$locale][$domain] = true;
            }
        }

        $messagesProperty->setValue($catalog, $messages);
        $loadedProperty->setValue($catalog, $loaded);
    }

    private function callPrivateMethod(Catalog $catalog, string $methodName, mixed ...$args): mixed
    {
        $reflection = new \ReflectionClass($catalog);
        $method = $reflection->getMethod($methodName);

        return $method->invoke($catalog, ...$args);
    }
}
