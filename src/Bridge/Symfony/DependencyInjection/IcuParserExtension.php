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

namespace IcuParser\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class IcuParserExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $defaultLocale = $config['default_locale'];
        if (!\is_string($defaultLocale) || '' === $defaultLocale) {
            $defaultLocale = null;
        }
        if (null === $defaultLocale && $container->hasParameter('kernel.default_locale')) {
            $kernelDefaultLocale = $container->getParameter('kernel.default_locale');
            if (\is_string($kernelDefaultLocale) && '' !== $kernelDefaultLocale) {
                $defaultLocale = $kernelDefaultLocale;
            }
        }

        $cacheDir = $config['cache_dir'];
        if (!\is_string($cacheDir) || '' === $cacheDir) {
            $cacheDir = null;
        }
        if (null === $cacheDir && $container->hasParameter('kernel.cache_dir')) {
            $kernelCacheDir = $container->getParameter('kernel.cache_dir');
            if (\is_string($kernelCacheDir) && '' !== $kernelCacheDir) {
                $cacheDir = rtrim($kernelCacheDir, '/').'/icu-parser';
            }
        }

        $translationPaths = $config['translation_paths'];
        if (!\is_array($translationPaths)) {
            $translationPaths = [];
        }
        $translationPaths = array_values(array_filter($translationPaths, static fn ($path): bool => \is_string($path) && '' !== $path));

        $defaultDomain = $config['default_domain'];
        if (!\is_string($defaultDomain) || '' === $defaultDomain) {
            $defaultDomain = 'messages';
        }

        $container->setParameter('icu_parser.translation_paths', $translationPaths);
        $container->setParameter('icu_parser.default_locale', $defaultLocale);
        $container->setParameter('icu_parser.cache_dir', $cacheDir);
        $container->setParameter('icu_parser.default_domain', $defaultDomain);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }

    #[\Override]
    public function getAlias(): string
    {
        return 'icu_parser';
    }
}
