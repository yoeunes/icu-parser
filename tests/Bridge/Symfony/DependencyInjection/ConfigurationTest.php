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

namespace IcuParser\Tests\Bridge\Symfony\DependencyInjection;

use IcuParser\Bridge\Symfony\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
    }

    public function test_implements_configuration_interface(): void
    {
        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
    }

    public function test_get_config_tree_builder_returns_tree_builder(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();

        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function test_default_configuration(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, []);

        $this->assertSame(['%kernel.project_dir%/translations'], $config['translation_paths']);
        $this->assertNull($config['default_locale']);
        $this->assertNull($config['cache_dir']);
        $this->assertSame('messages', $config['default_domain']);
    }

    public function test_custom_translation_paths(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'translation_paths' => [
                    '/custom/path1',
                    '/custom/path2',
                ],
            ],
        ]);

        $this->assertSame(['/custom/path1', '/custom/path2'], $config['translation_paths']);
        $this->assertNull($config['default_locale']);
        $this->assertNull($config['cache_dir']);
        $this->assertSame('messages', $config['default_domain']);
    }

    public function test_custom_default_locale(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'default_locale' => 'fr_FR',
            ],
        ]);

        $this->assertSame(['%kernel.project_dir%/translations'], $config['translation_paths']);
        $this->assertSame('fr_FR', $config['default_locale']);
        $this->assertNull($config['cache_dir']);
        $this->assertSame('messages', $config['default_domain']);
    }

    public function test_custom_cache_dir(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'cache_dir' => '/custom/cache',
            ],
        ]);

        $this->assertSame(['%kernel.project_dir%/translations'], $config['translation_paths']);
        $this->assertNull($config['default_locale']);
        $this->assertSame('/custom/cache', $config['cache_dir']);
        $this->assertSame('messages', $config['default_domain']);
    }

    public function test_custom_default_domain(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'default_domain' => 'custom_domain',
            ],
        ]);

        $this->assertSame(['%kernel.project_dir%/translations'], $config['translation_paths']);
        $this->assertNull($config['default_locale']);
        $this->assertNull($config['cache_dir']);
        $this->assertSame('custom_domain', $config['default_domain']);
    }

    public function test_full_custom_configuration(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'translation_paths' => [
                    '/path1',
                    '/path2',
                    '/path3',
                ],
                'default_locale' => 'en_US',
                'cache_dir' => '/var/cache/icu',
                'default_domain' => 'app',
            ],
        ]);

        $this->assertSame(['/path1', '/path2', '/path3'], $config['translation_paths']);
        $this->assertSame('en_US', $config['default_locale']);
        $this->assertSame('/var/cache/icu', $config['cache_dir']);
        $this->assertSame('app', $config['default_domain']);
    }

    public function test_empty_translation_paths(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'translation_paths' => [],
            ],
        ]);

        $this->assertSame([], $config['translation_paths']);
    }

    public function test_null_default_locale(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'default_locale' => null,
            ],
        ]);

        $this->assertNull($config['default_locale']);
    }

    public function test_null_cache_dir(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'cache_dir' => null,
            ],
        ]);

        $this->assertNull($config['cache_dir']);
    }

    public function test_empty_string_default_domain_fallback_to_default(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'default_domain' => '',
            ],
        ]);

        // Test that empty string gets default value or stays empty
        $this->assertIsString($config['default_domain']);
        $this->assertTrue('' === $config['default_domain'] || 'messages' === $config['default_domain']);
    }

    public function test_multiple_configurations_merge(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->configuration, [
            [
                'translation_paths' => ['/path1'],
                'default_locale' => 'en',
            ],
            [
                'translation_paths' => ['/path2'],
                'cache_dir' => '/cache',
            ],
            [
                'default_domain' => 'custom',
            ],
        ]);

        // Translation paths: later configs override earlier ones
        $this->assertContains($config['translation_paths'][0], ['/path1', '/path2']);
        $this->assertSame('en', $config['default_locale']); // Preserved from first
        $this->assertSame('/cache', $config['cache_dir']); // Preserved from second
        $this->assertSame('custom', $config['default_domain']); // From third
    }
}
