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

final class DummyCache implements CatalogCacheInterface
{
    public function getLocaleMessages(string $fingerprint, string $locale, string $domain): ?array
    {
        return null;
    }

    public function setLocaleMessages(string $fingerprint, string $locale, string $domain, array $messages): void {}
}
