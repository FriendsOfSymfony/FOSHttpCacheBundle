<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
                ->booleanNode('debug')
                    ->defaultValue('%kernel.debug%')
                    ->info('Whether to send a debug header with the response to trigger a caching proxy to send debug information.')
                ->end()
                ->scalarNode('debug_header')
                    ->defaultValue('X-Cache-Debug')
                    ->info('The header to send if debug is true.')
                ->end()
                ->booleanNode('authorization_listener')
                    ->defaultFalse()
                    ->info('Whether to activate the authorization listener that early returns head request after the security check.')
                ->end()
            ->end()
        ;

        $this->addRulesSection($rootNode);
        $this->addProxyClientSection($rootNode);
        $this->addTagListenerSection($rootNode);
        $this->addFlashMessageListenerSection($rootNode);
        $this->addInvalidatorsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Add rules section
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    private function addRulesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('rule')
            ->children()
                ->arrayNode('rules')
                    ->cannotBeOverwritten()
                    ->fixXmlConfig('method')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')
                                ->defaultNull()
                                ->info('Match on this request path.')
                            ->end()
                            ->scalarNode('host')
                                ->defaultNull()
                                ->info('Match on this request host name.')
                            ->end()
                            ->arrayNode('methods')
                                ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->info('Match on this HTTP method.')
                            ->end()
                            ->arrayNode('ips')
                                ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->info('Match on the list of client ips.')
                            ->end()
                            ->arrayNode('attributes')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->info('Match on these request attributes.')
                            ->end()
                            ->scalarNode('unless_role')
                                ->defaultNull()
                                ->info('Skip this rule if the current user is granted the specified role.')
                            ->end()
                            ->arrayNode('controls')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->info('Add the specified cache control headers.')
                            ->end()
                            ->scalarNode('reverse_proxy_ttl')
                                ->defaultNull()
                                ->info('Specify an X-Reverse-Proxy-TTL header with a time in seconds for a caching proxy under your control.')
                            ->end()
                            ->arrayNode('vary')
                                ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->prototype('scalar')->end()
                            ->end()

                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Add proxy client section
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    private function addProxyClientSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('proxy_client')
                    ->children()
                        ->enumNode('default')
                            ->values(array('varnish', 'nginx'))
                            ->info('If you configure more than one proxy client, specify which client is the default.')
                        ->end()
                        ->arrayNode('varnish')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                    ->useAttributeAsKey('name')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts varnish is running on. May be hostname or ip, and with :port if not the default port 6081.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Add tag listener section
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    private function addTagListenerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('tag_listener')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('enabled')
                            ->values(array(true, false, 'auto'))
                            ->defaultValue('auto')
                            ->info('Allows to disable the listener for tag annotations when your project does not use the annotations. Enabled by default if you configure a proxy client.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Add flash message listener section
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    private function addFlashMessageListenerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('flash_message_listener')
                    ->canBeUnset()
                    ->canBeEnabled()
                    ->info('Activate the flash message listener that puts flash messages into a cookie.')
                    ->children()
                        ->scalarNode('name')
                            ->defaultValue('flashes')
                            ->info('Name of the cookie to set for flashes.')
                        ->end()
                        ->scalarNode('path')
                            ->defaultValue('/')
                            ->info('Cookie path validity.')
                        ->end()
                        ->scalarNode('host')
                            ->defaultNull()
                            ->info('Cookie host name validity.')
                        ->end()
                        ->scalarNode('secure')
                            ->defaultFalse()
                            ->info('Whether the cookie should only be transmitted over a secure HTTPS connection from the client.')
                        ->end()
                        ->scalarNode('httpOnly')
                            ->defaultTrue()
                            ->info('Whether the cookie will be made accessible only through the HTTP protocol.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Add invalidator section
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
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
