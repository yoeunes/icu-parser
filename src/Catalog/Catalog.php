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

namespace IcuParser\Catalog;

use IcuParser\Catalog\Cache\CatalogCacheInterface;
use IcuParser\Loader\TranslationExtractorInterface;

/**
 * Lazy translation catalog: locale -> domain -> id -> message.
 */
final class Catalog
{
    public const CACHE_VERSION = '1';

    /**
     * @var array<string, array<string, array<string, string>>>
     */
    private array $messages = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $loaded = [];

    /**
     * @param array<string, array<string, list<string>>> $index
     */
    public function __construct(
        private readonly array $index,
        private readonly TranslationExtractorInterface $extractor,
        private readonly CatalogCacheInterface $cache,
        private readonly string $fingerprint,
        private readonly ?string $defaultLocale = null,
        private readonly string $defaultDomain = 'messages',
    ) {}

    public function getMessage(string $id, ?string $locale = null, ?string $domain = null): ?string
    {
        $resolvedLocale = $this->resolveLocale($locale);
        $resolvedDomain = $this->resolveDomain($domain);

        if (null === $resolvedLocale) {
            return null;
        }

        $this->ensureLoaded($resolvedLocale, $resolvedDomain);

        return $this->messages[$resolvedLocale][$resolvedDomain][$id] ?? null;
    }

    public function has(string $id, ?string $locale = null, ?string $domain = null): bool
    {
        return null !== $this->getMessage($id, $locale, $domain);
    }

    /**
     * @return list<string>
     */
    public function getLocales(): array
    {
        return array_keys($this->index);
    }

    /**
     * @return list<string>
     */
    public function getDomains(string $locale): array
    {
        return array_keys($this->index[$locale] ?? []);
    }

    private function ensureLoaded(string $locale, string $domain): void
    {
        if (isset($this->loaded[$locale][$domain])) {
            return;
        }

        $cached = $this->cache->getLocaleMessages($this->fingerprint, $locale, $domain);
        if (null !== $cached) {
            $this->messages[$locale][$domain] = $cached;
            $this->loaded[$locale][$domain] = true;

            return;
        }

        $messages = [];
        foreach ($this->index[$locale][$domain] ?? [] as $path) {
            $extraction = $this->extractor->extract($path);
            foreach ($extraction->messages as $key => $value) {
                $messages[$key] = $value;
            }
        }

        $this->messages[$locale][$domain] = $messages;
        $this->loaded[$locale][$domain] = true;
        $this->cache->setLocaleMessages($this->fingerprint, $locale, $domain, $messages);
    }

    private function resolveLocale(?string $locale): ?string
    {
        if (null !== $locale && '' !== $locale) {
            return $locale;
        }

        if (null !== $this->defaultLocale && '' !== $this->defaultLocale) {
            return $this->defaultLocale;
        }

        $locales = array_keys($this->index);
        if (1 === \count($locales)) {
            return $locales[0];
        }

        return null;
    }

    private function resolveDomain(?string $domain): string
    {
        if (null !== $domain && '' !== $domain) {
            return $domain;
        }

        return $this->defaultDomain;
    }
}
