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

namespace IcuParser\Loader;

use IcuParser\Catalog\Cache\CatalogCacheInterface;
use IcuParser\Catalog\Cache\FilesystemCatalogCache;
use IcuParser\Catalog\Cache\NullCatalogCache;
use IcuParser\Catalog\Catalog;
use IcuParser\Catalog\CatalogInterface;

final class TranslationLoader
{
    private const DEFAULT_DOMAIN = 'messages';

    /**
     * @var list<string>
     */
    private readonly array $paths;

    private readonly CatalogCacheInterface $cache;

    private ?CatalogInterface $catalog = null;

    /**
     * @param list<string> $paths
     */
    public function __construct(
        array $paths,
        private readonly ?string $defaultLocale = null,
        ?string $cacheDir = null,
        private readonly TranslationExtractorInterface $extractor = new CompositeTranslationExtractor([
            new YamlTranslationExtractor(),
            new XliffTranslationExtractor(),
        ]),
        private readonly string $defaultDomain = self::DEFAULT_DOMAIN,
    ) {
        $this->paths = $this->normalizePaths($paths);
        $this->cache = null !== $cacheDir && '' !== $cacheDir
            ? new FilesystemCatalogCache($cacheDir)
            : new NullCatalogCache();
    }

    public function loadCatalog(): CatalogInterface
    {
        if (null !== $this->catalog) {
            return $this->catalog;
        }

        $indexData = $this->buildIndex();
        $this->catalog = new Catalog(
            $indexData['index'],
            $this->extractor,
            $this->cache,
            $indexData['fingerprint'],
            $this->defaultLocale,
            $this->defaultDomain,
        );

        return $this->catalog;
    }

    /**
     * @return iterable<TranslationEntry>
     */
    public function scan(): iterable
    {
        foreach ($this->iterateFiles() as $path) {
            $localeDomain = $this->resolveLocaleDomain($path);
            if (null === $localeDomain) {
                continue;
            }

            $extraction = $this->extractor->extract($path);
            foreach ($extraction->messages as $id => $message) {
                $line = $extraction->lines[$id] ?? null;

                yield new TranslationEntry(
                    $path,
                    $localeDomain['locale'],
                    $localeDomain['domain'],
                    $id,
                    $message,
                    $line,
                );
            }
        }
    }

    /**
     * @return array{index: array<string, array<string, list<string>>>, fingerprint: string}
     */
    private function buildIndex(): array
    {
        $index = [];
        $fingerprintParts = [];

        foreach ($this->iterateFiles() as $path) {
            $localeDomain = $this->resolveLocaleDomain($path);
            if (null === $localeDomain) {
                continue;
            }

            $locale = $localeDomain['locale'];
            $domain = $localeDomain['domain'];

            $index[$locale][$domain] ??= [];
            $index[$locale][$domain][] = $path;

            $mtime = filemtime($path);
            $fingerprintParts[] = $path.':'.(false === $mtime ? 0 : $mtime);
        }

        foreach ($index as $locale => $domains) {
            foreach ($domains as $domain => $paths) {
                $paths = array_values(array_unique($paths));
                sort($paths);
                $index[$locale][$domain] = $paths;
            }
        }

        sort($fingerprintParts);
        $seed = Catalog::CACHE_VERSION.'|'.$this->defaultLocale.'|'.$this->defaultDomain.'|'.implode('|', $fingerprintParts);

        return [
            'index' => $index,
            'fingerprint' => hash('sha256', $seed),
        ];
    }

    /**
     * @return \Generator<int, string>
     */
    private function iterateFiles(): \Generator
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                if ($this->extractor->supports($path)) {
                    yield $path;
                }

                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                    continue;
                }

                $filePath = $file->getPathname();
                if ($this->extractor->supports($filePath)) {
                    yield $filePath;
                }
            }
        }
    }

    /**
     * @return array{locale: string, domain: string}|null
     */
    private function resolveLocaleDomain(string $path): ?array
    {
        $basename = basename($path);
        $parts = explode('.', $basename);
        $partCount = \count($parts);

        if ($partCount < 2) {
            return null;
        }

        $extension = array_pop($parts);
        if (null === $extension || '' === $extension) {
            return null;
        }

        $locale = null;
        $domain = null;

        if ($partCount >= 3) {
            $locale = array_pop($parts);
            $domain = implode('.', $parts);
        } else {
            $domain = implode('.', $parts);
            $locale = $this->defaultLocale;
        }

        $locale = '' !== (string) $locale ? (string) $locale : $this->defaultLocale;
        if (null === $locale || '' === $locale) {
            return null;
        }

        $domain = $this->normalizeDomain($domain);
        if ('' === $domain) {
            $domain = $this->defaultDomain;
        }

        return [
            'locale' => $locale,
            'domain' => $domain,
        ];
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        if ('' === $domain) {
            return $domain;
        }

        if (str_ends_with($domain, '+intl-icu')) {
            return substr($domain, 0, -9);
        }

        return $domain;
    }

    /**
     * @param array<int, string> $paths
     *
     * @return list<string>
     */
    private function normalizePaths(array $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            if (!\is_string($path) || '' === $path) {
                continue;
            }

            $path = rtrim($path, '/');
            $normalized[] = $path;
        }

        $normalized = array_values(array_unique($normalized));

        return $normalized;
    }
}
