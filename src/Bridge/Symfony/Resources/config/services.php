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

use IcuParser\Catalog\Catalog;
use IcuParser\Catalog\CatalogInterface;
use IcuParser\IcuParser;
use IcuParser\Loader\TranslationLoader;
use IcuParser\Parser\Parser;
use IcuParser\Type\TypeInferer;
use IcuParser\Validation\SemanticValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(IcuParser::class);
    $services->set(Parser::class);
    $services->set(TypeInferer::class);
    $services->set(SemanticValidator::class);

    $services->set(TranslationLoader::class)
        ->args([
            '$paths' => param('icu_parser.translation_paths'),
            '$defaultLocale' => param('icu_parser.default_locale'),
            '$cacheDir' => param('icu_parser.cache_dir'),
            '$defaultDomain' => param('icu_parser.default_domain'),
        ]);

    $services->set(Catalog::class)
        ->factory([service(TranslationLoader::class), 'loadCatalog']);

    $services->alias(CatalogInterface::class, Catalog::class);
};
