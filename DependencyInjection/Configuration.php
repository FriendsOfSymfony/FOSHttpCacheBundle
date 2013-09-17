<?php

namespace Driebit\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('driebit_http_cache');

        $rootNode
            ->children()
                ->arrayNode('http_cache')->isRequired()
                    ->children()
                        ->arrayNode('varnish')
                            ->children()
                                ->scalarNode('host')->isRequired()->end()
                                ->arrayNode('ips')
                                    ->cannotBeEmpty()
                                    ->prototype('scalar')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('invalidators')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('origin_routes')
                                ->cannotBeEmpty()
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('invalidate_routes')
                                ->useAttributeAsKey('route_name')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('parameter_mapper')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
