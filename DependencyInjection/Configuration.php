<?php

namespace FOS\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $rootNode = $treeBuilder->root('fos_http_cache');

        $rootNode
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('authorization_listener')->defaultFalse()->end()
            ->end()
        ;

        $this->addRulesSection($rootNode);
        $this->addVarnishSection($rootNode);
        $this->addFlashMessageListenerSection($rootNode);
        $this->addInvalidatorsSection($rootNode);

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
                            ->arrayNode('method')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->info('HTTP method')
                            ->end()
                            ->arrayNode('ips')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->info('List of ips')
                            ->end()
                            ->arrayNode('attributes')
                                ->addDefaultsIfNotSet()
                                ->cannotBeEmpty()
                                ->treatNullLike(array())
                                ->info('Request attributes')
                            ->end()
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
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('host')->defaultNull()->info('Default host name')->end()
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

    private function addInvalidatorsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('invalidators')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('origin_routes')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('invalidate_routes')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('parameter_mapper')->end()
                                        ->booleanNode('ignore_extra_params')->defaultTrue()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
