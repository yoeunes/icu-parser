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

use IcuParser\Catalog\Catalog;
use IcuParser\Catalog\CatalogEntry;
use IcuParser\Loader\TranslationLoader;
use IcuParser\Tests\Support\FilesystemTestCase;

final class CatalogTest extends FilesystemTestCase
{
    private Catalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = new Catalog(
            new TranslationLoader([$this->createTempDir()], 'en'),
            new DummyExtractor(),
            new DummyCache(),
        );
    }

    public function test_get_message_with_id_and_default_locale_and_domain(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello World', $entry->message);
        $this->assertSame(2, $entry->line);
        $this->assertStringContainsString('test.yaml', $entry->file);
    }

    public function test_get_entry_with_id_and_custom_locale_uses_default_domain(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello World', $entry->message);
        $this->assertSame(2, $entry->line);
        $this->assertStringContainsString('test.yaml', $entry->file);
    }

    public function test_get_entry_with_id_and_custom_locale_uses_custom_domain(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->assertSame('app.hello', $entry->id);
        $this->assertSame('Hello World', $entry->message);
        $this->assertSame(2, $entry->line);
        $this->assertStringContainsString('test.yaml', $entry->file);
    }

    public function test_has_with_nonexistent_id_returns_false(): void
    {
        $this->assertFalse($this->catalog->has('nonexistent'));
    }

    public function test_has_with_null_id_returns_false(): void
    {
        $this->assertFalse($this->catalog->has(null));
    }

    public function test_has_with_null_locale_fallbacks_to_default(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
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

        $this->assertTrue($this->catalog->has('app.hello', 'en', 'messages'));
    }

    public function test_get_locales_returns_single_locale(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
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

        $locales = $this->catalog->getLocales();
        $this->assertCount(2, $locales);
        $this->assertContains('en', $locales);
        $this->assertContains('fr', $locales);
    }

    public function test_get_domains_returns_single_domain(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
            ],
            'admin' => [
                'admin' => [
                    'message' => 'Admin Only',
                    'file' => 'admin.yaml',
                    'line' => 10,
                ],
            ],
        ]);

        $domains = $this->catalog->getDomains('en');
        $this->assertCount(2, $domains);
        $this->assertContains('messages', $domains);
        $this->assertContains('admin', $domains);
    }

    public function test_get_entries_returns_single_entry(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->catalog->setCatalogData($this->catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
                'app.goodbye' => [
                    'message' => 'Goodbye',
                    'file' => 'test.yaml',
                    'line' => 15,
                ],
            ],
        ]);

        $entries = $this->catalog->getEntries('en', 'messages');
        $this->assertCount(2, $entries);
        $this->assertSame('app.hello', $entries['app.hello']->id);
        $this->assertSame('app.goodbye', $entries['app.goodbye']->id);
    }

    public function test_get_entries_with_different_locales_returns_filtered_entries(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
                'app.hello' => [
                    'message' => 'Bonjour',
                    'file' => 'test.yaml',
                    'line' => 5,
                ],
            ],
        ]);

        $enEntries = $this->catalog->getEntries('en', 'messages');
        $frEntries = $this->catalog->getEntries('fr', 'messages');

        $this->assertCount(1, $enEntries);
        $this->assertSame('app.hello', $enEntries['app.hello']->id);
        $this->assertCount(1, $frEntries);
        $this->assertSame('Bonjour', $frEntries['app.hello']->id);
    }

    public function test_get_entries_with_nonexistent_locale_returns_empty(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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
        $this->assertSame('en', $this->catalog->resolveLocale('en'));
        $this->assertSame('custom', $this->catalog->resolveLocale('custom'));
        $this->assertSame('default', $this->catalog->resolveLocale(null));
    }

    public function test_resolve_locale_with_valid_locale_and_custom_fallback(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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

        $this->assertSame('custom', $this->catalog->resolveLocale('custom'));
        $this->assertSame('en', $this->catalog->resolveLocale('en'));
    }

    public function test_resolve_locale_with_empty_locale_uses_default(): void
    {
        $this->assertSame('en', $this->catalog->resolveLocale(''));
        $this->assertSame('default', $this->catalog->resolveLocale(null));
        $this->assertSame('en', $this->catalog->resolveLocale(null));
    }

    public function test_resolve_locale_with_null_locale_uses_custom_fallback(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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

        $this->assertSame('custom', $this->catalog->resolveLocale('custom'));
        $this->assertSame('en', $this->catalog->resolveLocale('en'));
    }

    public function test_resolve_domain_with_valid_domain(): void
    {
        $this->assertSame('custom', $this->catalog->resolveDomain('custom'));
        $this->assertSame('default', $this->catalog->resolveDomain(null));
    }

    public function test_resolve_domain_with_empty_domain_uses_default(): void
    {
        $this->assertSame('messages', $this->catalog->resolveDomain(null));
    }

    public function test_resolve_domain_with_empty_domain_uses_custom(): void
    {
        $this->catalog->setCatalogData($this->catalog, [
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

        $this->assertSame('custom', $this->catalog->resolveDomain('custom'));
        $this->assertSame('default', $this->catalog->resolveDomain(null));
    }

    public function test_ensure_loaded_is_called_when_cache_miss(): void
    {
        $catalog = new Catalog(
            new TranslationLoader([$this->createTempDir()], 'en'),
            $this->createMock(TranslationExtractorInterface::class),
            $this->createMock(CatalogCacheInterface::class),
        );

        $catalog->ensureLoaded('en', 'messages');
    }

    public function test_ensure_loaded_is_not_called_when_cache_hit(): void
    {
        $catalog = new Catalog(
            new TranslationLoader([$this->createTempDir()], 'en'),
            $this->createMock(TranslationExtractorInterface::class),
            $this->createMock(CatalogCacheInterface::class),
        );

        $catalog->ensureLoaded('en', 'messages');

        // Cache hit
        $this->mockCache->expects($this->once())
            ->method('getLocaleMessages')
            ->willReturn(null);
        $catalog->ensureLoaded('en', 'messages');

        $this->assertSame(1, $this->mockCache->getMethodCalls('getLocaleMessages'));
    }

    public function test_ensure_loaded_with_null_cache_data(): void
    {
        $catalog = new Catalog(
            new TranslationLoader([$this->createTempDir()], 'en'),
            $this->createMock(TranslationExtractorInterface::class),
            $this->createMock(CatalogCacheInterface::class),
        );

        $catalog->ensureLoaded('en', 'messages');

        // Cache returns empty array
        $this->mockCache->expects($this->once())
            ->method('getLocaleMessages')
            ->willReturn(['app.hello' => ['message' => 'Hello World']]);
        $catalog->ensureLoaded('en', 'messages');

        // No cache call made
        $this->assertSame(1, $this->mockCache->getMethodCalls('getLocaleMessages'));
    }

    public function test_ensure_loaded_with_cache_exception(): void
    {
        $catalog = new Catalog(
            new TranslationLoader([$this->createTempDir()], 'en'),
            $this->createMock(TranslationExtractorInterface::class),
            $this->createMock(CatalogCacheInterface::class),
        );

        $catalog->ensureLoaded('en', 'messages');

        // Cache throws exception
        $this->mockCache->expects($this->once())
            ->method('getLocaleMessages')
            ->willThrowException(new \Exception('Cache error'));

        $this->expectException(\Exception::class);
        $catalog->ensureLoaded('en', 'messages');

        $this->assertSame(1, $this->mockCache->getMethodCalls('getLocaleMessages'));
    }

    public function test_hydrate_entries_filters_by_locale_and_domain(): void
    {
        $catalog = new Catalog(
            new TranslationLoader([$this->createTempDir()], 'en'),
            $this->createMock(TranslationExtractorInterface::class),
            $this->createMock(CatalogCacheInterface::class),
        );

        $catalog->setCatalogData($catalog, [
            'en' => [
                'messages' => [
                    'app.hello' => [
                        'message' => 'Hello World',
                        'file' => 'test.yaml',
                        'line' => 5,
                    ],
                ],
                'app.goodbye' => [
                    'message' => 'Goodbye',
                    'file' => 'test.yaml',
                    'line' => 15,
                ],
            ],
            'fr' => [
                'app.hello' => [
                    'message' => 'Bonjour',
                    'file' => 'test.yaml',
                    'line' => 5,
                ],
            ],
        ]);

        $this->assertSame(['app.hello'], $catalog->getEntries('en', 'messages'));
        $this->assertSame(['app.goodbye'], $catalog->getEntries('fr', 'messages'));
        $this->assertSame(['app.hello'], $catalog->getEntries('en', 'messages'));
        $this->assertSame(['app.hello'], $catalog->getEntries('fr', 'messages'));
    }

    private function setCatalogData(Catalog $catalog, array $data): void
    {
        foreach ($data as $locale => $localeData) {
            foreach ($localeData as $domain => $domainData) {
                foreach ($domainData as $id => $entryData) {
                    $catalog->messages[$locale][$domain][$id] = new CatalogEntry(
                        $id,
                        $entryData['message'],
                        $entryData['file'],
                        $entryData['line'],
                    );
                }
            }
        }
    }

    private function createTempDir(): string
    {
        return sys_get_temp_dir().'/icu_parser_test_'.uniqid().'/translations';
    }

    private function createMock(string $class): object
    {
        return $this->createMock($class);
    }
}
