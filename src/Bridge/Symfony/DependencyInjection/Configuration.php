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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('icu_parser');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('translation_paths')
                    ->scalarPrototype()->end()
                    ->defaultValue(['%kernel.project_dir%/translations'])
                ->end()
                ->scalarNode('default_locale')
                    ->defaultNull()
                ->end()
                ->scalarNode('cache_dir')
                    ->defaultNull()
                ->end()
                ->scalarNode('default_domain')
                    ->defaultValue('messages')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
