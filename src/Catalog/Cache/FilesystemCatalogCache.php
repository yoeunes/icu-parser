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

namespace IcuParser\Catalog\Cache;

final readonly class FilesystemCatalogCache implements CatalogCacheInterface
{
    public function __construct(private string $directory) {}

    public function getLocaleMessages(string $fingerprint, string $locale, string $domain): ?array
    {
        $path = $this->buildPath($fingerprint, $locale, $domain);
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            return null;
        }

        $data = @unserialize($contents, ['allowed_classes' => false]);
        if (!\is_array($data)) {
            return null;
        }

        $messages = [];
        foreach ($data as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                return null;
            }

            $messages[$key] = $value;
        }

        return $messages;
    }

    public function setLocaleMessages(string $fingerprint, string $locale, string $domain, array $messages): void
    {
        $path = $this->buildPath($fingerprint, $locale, $domain);
        $directory = \dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            return;
        }

        @file_put_contents($path, serialize($messages));
    }

    private function buildPath(string $fingerprint, string $locale, string $domain): string
    {
        $safeLocale = $this->sanitizeSegment($locale);
        $safeDomain = $this->sanitizeSegment($domain);

        return rtrim($this->directory, '/').'/'.$fingerprint.'/'.$safeLocale.'@'.$safeDomain.'.cache';
    }

    private function sanitizeSegment(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $value) ?? $value;
    }
}
