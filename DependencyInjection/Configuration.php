<?php

namespace Liip\CacheControlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('liip_cache_control', 'array');

        $rootNode
            ->fixXmlConfig('rule', 'rules')
            ->children()
                ->arrayNode('rules')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')->defaultNull()->end()
                            ->scalarNode('reverse_proxy_ttl')->defaultNull()->end()
                            ->arrayNode('controls')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('varnish')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('ips')
                            ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('domain')->defaultNull()->end()
                        ->scalarNode('port')->defaultNull()->end()
                    ->end()
                ->end()
                ->booleanNode('authorization_listener')->defaultFalse()->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
