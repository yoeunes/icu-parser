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

interface CatalogCacheInterface
{
    /**
     * @return array<string, array{message: string, file: string, line: int|null}>|null
     */
    public function getLocaleMessages(string $fingerprint, string $locale, string $domain): ?array;

    /**
     * @param array<string, array{message: string, file: string, line: int|null}> $messages
     */
    public function setLocaleMessages(string $fingerprint, string $locale, string $domain, array $messages): void;
}
