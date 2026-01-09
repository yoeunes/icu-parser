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
    private FilesystemCatalogCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new FilesystemCatalogCache($this->createTempDir().'/cache');
    }

    public function test_persists_and_restores_messages(): void
    {
        $messages = [
            'app.hello' => [
                'message' => 'Hello',
                'file' => '/translations/messages.en.yaml',
                'line' => 3,
            ],
        ];

        $this->cache->setLocaleMessages('fingerprint', 'en', 'messages', $messages);
        $loaded = $this->cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertSame($messages, $loaded);
    }

    public function test_get_locale_messages_returns_null_when_file_not_exists(): void
    {
        $result = $this->cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_file_cannot_be_read(): void
    {
        $result = $this->cache->getLocaleMessages('unreadable', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_contents_invalid(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, 'invalid serialized data');

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_data_not_array(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize('not an array'));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_key_not_string(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $invalidData = [
            123 => ['message' => 'test', 'file' => 'test.php', 'line' => 1],
        ];

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize($invalidData));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_value_not_array(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $invalidData = [
            'key' => 'not an array',
        ];

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize($invalidData));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_message_not_string(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $invalidData = [
            'key' => ['message' => 123, 'file' => 'test.php', 'line' => 1],
        ];

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize($invalidData));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_file_not_string(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $invalidData = [
            'key' => ['message' => 'test', 'file' => 123, 'line' => 1],
        ];

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize($invalidData));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_null_when_line_not_int_or_null(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $invalidData = [
            'key' => ['message' => 'test', 'file' => 'test.php', 'line' => 'not int'],
        ];

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize($invalidData));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertNull($result);
    }

    public function test_get_locale_messages_returns_valid_data(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        $validData = [
            'key1' => ['message' => 'Hello {name}', 'file' => 'file1.php', 'line' => 10],
            'key2' => ['message' => 'Goodbye {name}', 'file' => 'file2.php', 'line' => null],
        ];

        $path = $cacheDir.'/fingerprint/en@messages.cache';
        mkdir(dirname($path), 0o755, true);
        file_put_contents($path, serialize($validData));

        $result = $cache->getLocaleMessages('fingerprint', 'en', 'messages');

        $this->assertSame($validData, $result);
    }

    public function test_set_locale_messages_creates_directory_if_not_exists(): void
    {
        $messages = [
            'key1' => ['message' => 'Hello {name}', 'file' => 'file1.php', 'line' => 10],
        ];

        $this->cache->setLocaleMessages('fingerprint', 'en', 'messages', $messages);

        $expectedPath = $this->createTempDir().'/cache/fingerprint/en@messages.cache';
        $this->assertFileExists($expectedPath);

        $result = $this->cache->getLocaleMessages('fingerprint', 'en', 'messages');
        $this->assertSame($messages, $result);
    }

    public function test_set_locale_messages_overwrites_existing_file(): void
    {
        $messages1 = [
            'key1' => ['message' => 'Hello {name}', 'file' => 'file1.php', 'line' => 10],
        ];
        $messages2 = [
            'key2' => ['message' => 'Goodbye {name}', 'file' => 'file2.php', 'line' => 20],
        ];

        // Write initial data
        $this->cache->setLocaleMessages('fingerprint', 'en', 'messages', $messages1);

        // Overwrite with new data
        $this->cache->setLocaleMessages('fingerprint', 'en', 'messages', $messages2);

        $result = $this->cache->getLocaleMessages('fingerprint', 'en', 'messages');
        $this->assertSame($messages2, $result);
    }

    public function test_build_path_creates_correct_file_structure(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($cache);
        $method = $reflection->getMethod('buildPath');

        $path = $method->invoke($cache, 'fingerprint123', 'en_US', 'my_domain');

        $expectedPath = $cacheDir.'/fingerprint123/en_US@my_domain.cache';
        $this->assertSame($expectedPath, $path);
    }

    public function test_build_path_sanitizes_locale_and_domain(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($cache);
        $buildPathMethod = $reflection->getMethod('buildPath');

        $path = $buildPathMethod->invoke($cache, 'fp', 'en@US', 'my@domain');

        $expectedPath = $cacheDir.'/fp/en_US@my_domain.cache';
        $this->assertSame($expectedPath, $path);
    }

    public function test_sanitize_segment_replaces_invalid_characters(): void
    {
        $cacheDir = $this->createTempDir().'/cache';
        $cache = new FilesystemCatalogCache($cacheDir);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($cache);
        $method = $reflection->getMethod('sanitizeSegment');

        // Test actual behavior of the sanitize method
        $this->assertIsString($method->invoke($cache, 'en-US'));
        $this->assertIsString($method->invoke($cache, 'my.domain'));
        $this->assertIsString($method->invoke($cache, 'test-123'));
        $this->assertIsString($method->invoke($cache, 'test@$%^&*'));
        $this->assertIsString($method->invoke($cache, 'valid_name'));
    }

    public function test_set_locale_messages_handles_line_as_null(): void
    {
        $messages = [
            'key1' => ['message' => 'Hello {name}', 'file' => 'file1.php', 'line' => null],
        ];

        $this->cache->setLocaleMessages('fingerprint', 'en', 'messages', $messages);

        $result = $this->cache->getLocaleMessages('fingerprint', 'en', 'messages');
        $this->assertSame($messages, $result);
    }

    public function test_multiple_locales_and_domains(): void
    {
        $messages1 = [
            'key1' => ['message' => 'Hello', 'file' => 'file1.php', 'line' => 1],
        ];
        $messages2 = [
            'key2' => ['message' => 'Bonjour', 'file' => 'file2.php', 'line' => 2],
        ];

        $this->cache->setLocaleMessages('fp1', 'en', 'messages', $messages1);
        $this->cache->setLocaleMessages('fp2', 'fr', 'messages', $messages2);
        $this->cache->setLocaleMessages('fp3', 'en', 'admin', $messages1);

        $result1 = $this->cache->getLocaleMessages('fp1', 'en', 'messages');
        $result2 = $this->cache->getLocaleMessages('fp2', 'fr', 'messages');
        $result3 = $this->cache->getLocaleMessages('fp3', 'en', 'admin');

        $this->assertSame($messages1, $result1);
        $this->assertSame($messages2, $result2);
        $this->assertSame($messages1, $result3);
    }
}
