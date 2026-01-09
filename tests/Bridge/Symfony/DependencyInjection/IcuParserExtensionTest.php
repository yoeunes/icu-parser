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

use IcuParser\Bridge\Symfony\DependencyInjection\IcuParserExtension;
use IcuParser\Bridge\Twig\TwigTranslationExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Environment;

final class IcuParserExtensionTest extends TestCase
{
    private IcuParserExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new IcuParserExtension();
    }

    public function test_get_alias(): void
    {
        $this->assertSame('icu_parser', $this->extension->getAlias());
    }

    public function test_load_with_default_configuration(): void
    {
        $container = new ContainerBuilder();
        $configs = [];

        $this->extension->load($configs, $container);

        $this->assertTrue($container->hasParameter('icu_parser.translation_paths'));
        $this->assertTrue($container->hasParameter('icu_parser.default_locale'));
        $this->assertTrue($container->hasParameter('icu_parser.cache_dir'));
        $this->assertTrue($container->hasParameter('icu_parser.default_domain'));

        $this->assertSame(['%kernel.project_dir%/translations'], $container->getParameter('icu_parser.translation_paths'));
        $this->assertNull($container->getParameter('icu_parser.default_locale'));
        $this->assertNull($container->getParameter('icu_parser.cache_dir'));
        $this->assertSame('messages', $container->getParameter('icu_parser.default_domain'));
    }

    public function test_load_with_custom_configuration(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            [
                'translation_paths' => ['/custom/path1', '/custom/path2'],
                'default_locale' => 'fr_FR',
                'cache_dir' => '/custom/cache',
                'default_domain' => 'custom_domain',
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertSame(['/custom/path1', '/custom/path2'], $container->getParameter('icu_parser.translation_paths'));
        $this->assertSame('fr_FR', $container->getParameter('icu_parser.default_locale'));
        $this->assertSame('/custom/cache', $container->getParameter('icu_parser.cache_dir'));
        $this->assertSame('custom_domain', $container->getParameter('icu_parser.default_domain'));
    }

    public function test_load_with_empty_default_locale(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            [
                'default_locale' => '',
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertNull($container->getParameter('icu_parser.default_locale'));
    }

    public function test_load_with_empty_cache_dir(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            [
                'cache_dir' => '',
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertNull($container->getParameter('icu_parser.cache_dir'));
    }

    public function test_load_with_kernel_default_locale_fallback(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.default_locale', 'en_US');
        $configs = [
            [
                'default_locale' => '', // Empty string should trigger fallback
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertSame('en_US', $container->getParameter('icu_parser.default_locale'));
    }

    public function test_load_with_kernel_cache_dir_fallback(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', '/var/cache');
        $configs = [
            [
                'cache_dir' => '', // Empty string should trigger fallback
            ],
        ];

        $this->extension->load($configs, $container);

        $expectedCacheDir = '/var/cache/icu-parser';
        $this->assertSame($expectedCacheDir, $container->getParameter('icu_parser.cache_dir'));
    }

    public function test_load_with_invalid_translation_paths(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            [
                'translation_paths' => ['invalid'], // Valid array with invalid content
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertSame(['invalid'], $container->getParameter('icu_parser.translation_paths'));
    }

    public function test_load_filters_empty_translation_paths(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            [
                'translation_paths' => [
                    '/valid/path',
                    '', // Empty string should be filtered
                    'another/valid/path',
                    null, // Null should be filtered (though config processor prevents this)
                ],
            ],
        ];

        $this->extension->load($configs, $container);

        $expectedPaths = ['/valid/path', 'another/valid/path'];
        $this->assertSame($expectedPaths, $container->getParameter('icu_parser.translation_paths'));
    }

    public function test_load_with_empty_default_domain_fallback(): void
    {
        $container = new ContainerBuilder();
        $configs = [
            [
                'default_domain' => '',
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertSame('messages', $container->getParameter('icu_parser.default_domain'));
    }

    public function test_load_without_twig_environment(): void
    {
        // Mock class_exists to return false for Twig\Environment
        $container = new ContainerBuilder();
        $configs = [];

        // This test verifies that the extension doesn't fail when Twig is not available
        $this->extension->load($configs, $container);

        $this->assertTrue($container->hasParameter('icu_parser.translation_paths'));
        // Twig services should not be registered
        $this->assertFalse($container->hasDefinition(TwigTranslationExtractor::class));
    }

    public function test_load_with_twig_definition_available(): void
    {
        if (!class_exists(Environment::class)) {
            $this->markTestSkipped('Twig not available');
        }

        $container = new ContainerBuilder();
        $container->setDefinition('twig', new Definition(\stdClass::class));
        $configs = [];

        $this->extension->load($configs, $container);

        // Should register Twig translation extractor
        $this->assertTrue($container->hasDefinition(TwigTranslationExtractor::class));
    }

    public function test_load_with_twig_alias_available(): void
    {
        if (!class_exists(Environment::class)) {
            $this->markTestSkipped('Twig not available');
        }

        $container = new ContainerBuilder();
        $container->setAlias('twig', 'twig.service');
        $configs = [];

        $this->extension->load($configs, $container);

        // Should register Twig translation extractor
        $this->assertTrue($container->hasDefinition(TwigTranslationExtractor::class));
    }

    public function test_load_services_are_loaded(): void
    {
        $container = new ContainerBuilder();
        $configs = [];

        $this->expectNotToPerformAssertions();

        $this->extension->load($configs, $container);
    }
}
