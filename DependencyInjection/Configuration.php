<?php

namespace Liip\CacheControlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition,
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
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('authorization_listener')->defaultFalse()->end()
            ->end()
        ;

        $this->addRulesSection($rootNode);
        $this->addVarnishSection($rootNode);
        $this->addFlashMessageListenerSection($rootNode);

        return $treeBuilder;
    }

    private function addRulesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('rule', 'rules')
            ->children()
                ->arrayNode('rules')
                    ->cannotBeOverwritten()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('unless_role')->defaultNull()->end()
                            ->scalarNode('path')->defaultNull()->info('URL path info')->end()
                            ->scalarNode('domain')->defaultNull()->info('depreciated, use host instead')->end()
                            ->scalarNode('host')->defaultNull()->info('URL host name')->end()
                            ->scalarNode('controller')->defaultNull()->info('controller action name')->end()
                            ->scalarNode('reverse_proxy_ttl')->defaultNull()->end()
                            ->arrayNode('controls')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('vary')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addVarnishSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('varnish')
                    ->children()
                        ->arrayNode('ips')
                            ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('domain')->defaultNull()->info('depreciated, use host instead')->end()
                        ->scalarNode('host')->defaultNull()->info('URL host name')->end()
                        ->scalarNode('port')->defaultNull()->end()
                        ->enumNode('purge_instruction')
                            ->values(array('purge', 'ban'))
                            ->defaultValue('purge')
                            ->info('the purge instruction (purge in Varnish 2, ban possible since Varnish 3)')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addFlashMessageListenerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('flash_message_listener')
                    ->canBeUnset()
                    ->treatFalseLike(array('enabled' => false))
                    ->treatTrueLike(array('enabled' => true))
                    ->treatNullLike(array('enabled' => true))
                    ->children()
                        ->scalarNode('enabled')->defaultTrue()->end()
                        ->scalarNode('name')->defaultValue('flashes')->end()
                        ->scalarNode('path')->defaultValue('/')->end()
                        ->scalarNode('domain')->defaultNull()->info('depreciated, use host instead')->end()
                        ->scalarNode('host')->defaultNull()->info('URL host name')->end()
                        ->scalarNode('secure')->defaultFalse()->end()
                        ->scalarNode('httpOnly')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();
    }

}
