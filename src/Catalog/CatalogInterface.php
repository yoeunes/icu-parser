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

interface CatalogInterface
{
    public function getMessage(string $id, ?string $locale = null, ?string $domain = null): ?string;

    public function getEntry(string $id, ?string $locale = null, ?string $domain = null): ?CatalogEntry;

    public function has(string $id, ?string $locale = null, ?string $domain = null): bool;

    /**
     * @return list<string>
     */
    public function getLocales(): array;

    /**
     * @return list<string>
     */
    public function getDomains(string $locale): array;

    /**
     * @return array<string, CatalogEntry>
     */
    public function getEntries(string $locale, ?string $domain = null): array;
}
