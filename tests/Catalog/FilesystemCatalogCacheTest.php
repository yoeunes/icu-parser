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

use IcuParser\Catalog\Cache\FilesystemCatalogCache;
use IcuParser\Tests\Support\FilesystemTestCase;

final class FilesystemCatalogCacheTest extends FilesystemTestCase
{
    public function test_persists_and_restores_messages(): void
    {
        $cache = new FilesystemCatalogCache($this->createTempDir().'/cache');
        $messages = [
            'app.hello' => [
                'message' => 'Hello',
                'file' => '/translations/messages.en.yaml',
                'line' => 3,
            ],
        ];

        $cache->setLocaleMessages('fingerprint', 'en', 'messages', $messages);
        $loaded = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertSame($messages, $loaded);
    }
}
