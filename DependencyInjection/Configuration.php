<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param Boolean $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fos_http_cache');

        $rootNode
            ->validate()
                ->ifTrue(function ($v) {
                    return $v['cache_manager']['enabled']
                        && !isset($v['proxy_client'])
                        && !isset($v['cache_manager']['custom_proxy_client'])
                    ;
                })
                ->then(function ($v) {
                    if ('auto' === $v['cache_manager']['enabled']) {
                        $v['cache_manager']['enabled'] = false;

                        return $v;
                    }
                    throw new InvalidConfigurationException('You need to configure a proxy_client or specify a custom_proxy_client to use the cache_manager.');
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) {return $v['tags']['enabled'] && !$v['cache_manager']['enabled'];})
                ->then(function ($v) {
                    if ('auto' === $v['tags']['enabled']) {
                        $v['tags']['enabled'] = false;

                        return $v;
                    }
                    throw new InvalidConfigurationException('You need to configure a proxy_client to get the cache_manager needed for tag handling.');
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) {return $v['tags']['rules'] && !$v['tags']['enabled'];})
                ->thenInvalid('You need to enable the cache_manager and tags to use rules.')
            ->end()
            ->validate()
                ->ifTrue(function ($v) {return $v['invalidation']['enabled'] && !$v['cache_manager']['enabled'];})
                ->then(function ($v) {
                    if ('auto' === $v['invalidation']['enabled']) {
                        $v['invalidation']['enabled'] = false;

                        return $v;
                    }
                    throw new InvalidConfigurationException('You need to configure a proxy_client to get the cache_manager needed for invalidation handling.');
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) {return $v['invalidation']['rules'] && !$v['invalidation']['enabled'];})
                ->thenInvalid('You need to enable the cache_manager and invalidation to use rules.')
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    return isset($v['test'])
                        && $v['test']['client']['varnish']['enabled']
                        && !isset($v['proxy_client']['varnish']);
                    })
                ->then(function ($v) {
                    if ('auto' === $v['test']['client']['varnish']['enabled']) {
                        $v['test']['client']['varnish']['enabled'] = false;

                        return $v;
                    }
                    throw new InvalidConfigurationException('You need to configure the Varnish proxy_client to use the Varnish test client');
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    if (isset($v['test'])) {
                        return $v['test']['client']['nginx']['enabled'] && !isset($v['proxy_client']['nginx']);
                    }
                })
                ->then(function ($v) {
                    if ('auto' === $v['test']['client']['nginx']['enabled']) {
                        $v['test']['client']['nginx']['enabled'] = false;

                        return $v;
                    }
                    throw new InvalidConfigurationException('You need to configure the Nginx proxy_client to use the Nginx test client');
                })
            ->end()
            ->validate()
                ->ifTrue(
                    function ($v) {
                        return $v['user_context']['logout_handler']['enabled']
                            && !isset($v['proxy_client']);
                    }
                )
                ->then(function ($v) {
                    if ('auto' === $v['user_context']['logout_handler']['enabled']) {
                        $v['user_context']['logout_handler']['enabled'] = false;

                        return $v;
                    }
                    throw new InvalidConfigurationException('You need to configure a proxy_client for the logout_handler.');
                })
        ;

        $this->addCacheControlSection($rootNode);
        $this->addProxyClientSection($rootNode);
        $this->addCacheManagerSection($rootNode);
        $this->addTagSection($rootNode);
        $this->addInvalidationSection($rootNode);
        $this->addUserContextListenerSection($rootNode);
        $this->addFlashMessageSection($rootNode);
        $this->addTestSection($rootNode);
        $this->addDebugSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Cache header control main section.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addCacheControlSection(ArrayNodeDefinition $rootNode)
    {
        $rules = $rootNode
            ->children()
                ->arrayNode('cache_control')
                    ->fixXmlConfig('rule')
                    ->children()
                        ->arrayNode('defaults')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('overwrite')
                                    ->info('Whether to overwrite existing cache headers')
                                    ->defaultFalse()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('rules')
                            ->prototype('array')
                                ->children();

        $this->addMatch($rules);
        $rules
            ->arrayNode('headers')
                ->isRequired()
                // todo validate there is some header defined
                ->children()
                    ->enumNode('overwrite')
                        ->info('Whether to overwrite cache headers for this rule, defaults to the cache_control.defaults.overwrite setting')
                        ->values(array('default', true, false))
                        ->defaultValue('default')
                    ->end()
                    ->arrayNode('cache_control')
                        ->info('Add the specified cache control directives.')
                        ->children()
                            ->scalarNode('max_age')->end()
                            ->scalarNode('s_maxage')->end()
                            ->booleanNode('private')->end()
                            ->booleanNode('public')->end()
                            ->booleanNode('must_revalidate')->end()
                            ->booleanNode('proxy_revalidate')->end()
                            ->booleanNode('no_transform')->end()
                            ->booleanNode('no_cache')->end()
                            ->scalarNode('stale_if_error')->end()
                            ->scalarNode('stale_while_revalidate')->end()
                        ->end()
                    ->end()
                    ->scalarNode('etag')
                        ->defaultValue(false)
                        ->info('Set a simple ETag which is just the md5 hash of the response body')
                    ->end()
                    ->scalarNode('last_modified')
                        ->validate()
                            ->ifTrue(function ($v) {
                                if (is_string($v)) {
                                    new \DateTime($v);
                                }

                                return false;
                            })
                            ->thenInvalid('') // this will never happen as new DateTime will throw an exception if $v is no date
                        ->end()
                        ->info('Set a default last modified timestamp if none is set yet. Value must be parseable by DateTime')
                    ->end()
                    ->scalarNode('reverse_proxy_ttl')
                        ->defaultNull()
                        ->info('Specify an X-Reverse-Proxy-TTL header with a time in seconds for a caching proxy under your control.')
                    ->end()
                    ->arrayNode('vary')
                        ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                        ->prototype('scalar')->end()
                        ->info('Define a list of additional headers on which the response varies.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Shared configuration between cache control, tags and invalidation.
     *
     * @param NodeBuilder $rules
     */
    private function addMatch(NodeBuilder $rules)
    {
        $rules
            ->arrayNode('match')
                ->cannotBeOverwritten()
                ->isRequired()
                ->fixXmlConfig('method')
                ->fixXmlConfig('ip')
                ->fixXmlConfig('attribute')
                ->validate()
                    ->ifTrue(function ($v) {return !empty($v['additional_cacheable_status']) && !empty($v['match_response']);})
                    ->thenInvalid('You may not set both additional_cacheable_status and match_response.')
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {return !empty($v['match_response']) && !class_exists('Symfony\Component\ExpressionLanguage\ExpressionLanguage');})
                    ->thenInvalid('Configured a match_response but ExpressionLanguage is not available')
                ->end()
                ->children()
                    ->scalarNode('path')
                        ->defaultNull()
                        ->info('Request path.')
                    ->end()
                    ->scalarNode('host')
                        ->defaultNull()
                        ->info('Request host name.')
                    ->end()
                    ->arrayNode('methods')
                        ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->info('Request HTTP methods.')
                    ->end()
                    ->arrayNode('ips')
                        ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->info('List of client IPs.')
                    ->end()
                    ->arrayNode('attributes')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->info('Regular expressions on request attributes.')
                    ->end()
                    ->arrayNode('additional_cacheable_status')
                        ->prototype('scalar')->end()
                        ->info('Additional response HTTP status codes that will match.')
                    ->end()
                    ->scalarNode('match_response')
                        ->defaultNull()
                        ->info('Expression to decide whether response should be matched. Replaces HTTP code check and additional_cacheable_status.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addProxyClientSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('proxy_client')
                    ->children()
                        ->enumNode('default')
                            ->values(array('varnish', 'nginx', 'symfony'))
                            ->info('If you configure more than one proxy client, specify which client is the default.')
                        ->end()
                        ->arrayNode('varnish')
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                    ->useAttributeAsKey('name')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Varnish is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                                ->scalarNode('guzzle_client')
                                    ->defaultNull()
                                    ->info('Guzzle service to use for customizing the requests.')
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('nginx')
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                    ->useAttributeAsKey('name')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Nginx is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                                ->scalarNode('guzzle_client')
                                    ->defaultNull()
                                    ->info('Guzzle service to use for customizing the requests.')
                                ->end()
                                ->scalarNode('purge_location')
                                    ->defaultValue('')
                                    ->info('Path to trigger the purge on Nginx for different location purge.')
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('symfony')
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                    ->useAttributeAsKey('name')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Symfony HttpCache is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                                ->scalarNode('guzzle_client')
                                    ->defaultNull()
                                    ->info('Guzzle service to use for customizing the requests.')
                                ->end()
                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end();
    }

    private function addTestSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('test')
                    ->children()
                        ->scalarNode('cache_header')
                            ->defaultValue('X-Cache')
                            ->info('HTTP cache hit/miss header')
                        ->end()
                        ->arrayNode('proxy_server')
                            ->info('Configure how caching proxy will be run in your tests')
                            ->children()
                                ->enumNode('default')
                                    ->values(array('varnish', 'nginx'))
                                    ->info('If you configure more than one proxy server, specify which client is the default.')
                                ->end()
                                ->arrayNode('varnish')
                                    ->children()
                                        ->scalarNode('config_file')->isRequired()->end()
                                        ->scalarNode('binary')->defaultValue('varnishd')->end()
                                        ->integerNode('port')->defaultValue(6181)->end()
                                        ->scalarNode('ip')->defaultValue('127.0.0.1')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('nginx')
                                    ->children()
                                        ->scalarNode('config_file')->isRequired()->end()
                                        ->scalarNode('binary')->defaultValue('nginx')->end()
                                        ->integerNode('port')->defaultValue(8080)->end()
                                        ->scalarNode('ip')->defaultValue('127.0.0.1')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('client')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('default')
                                    ->values(array('varnish', 'nginx'))
                                    ->info('If you configure more than one proxy client, specify which client is the default.')
                                ->end()
                                ->arrayNode('varnish')
                                    ->addDefaultsIfNotSet()
                                    ->canBeEnabled()
                                    ->children()
                                        ->enumNode('enabled')
                                            ->values(array(true, false, 'auto'))
                                            ->defaultValue('auto')
                                            ->info('Whether to enable the Varnish test client.')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('nginx')
                                    ->addDefaultsIfNotSet()
                                    ->canBeEnabled()
                                    ->children()
                                        ->enumNode('enabled')
                                            ->values(array(true, false, 'auto'))
                                            ->defaultValue('auto')
                                            ->info('Whether to enable the Nginx test client.')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Cache manager main section
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addCacheManagerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('cache_manager')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function ($v) {
                            $v['enabled'] = isset($v['enabled']) ? $v['enabled'] : true;

                            return $v;
                        })
                    ->end()
                    ->info('Configure the cache manager. Needs a proxy_client to be configured.')
                    ->children()
                        ->enumNode('enabled')
                            ->values(array(true, false, 'auto'))
                            ->defaultValue('auto')
                            ->info('Allows to disable the invalidation manager. Enabled by default if you configure a proxy client.')
                        ->end()
                        ->scalarNode('custom_proxy_client')
                            ->info('Service name of a custom proxy client to use. If you configure a proxy client, that client will be used by default.')
                        ->end()
                        ->enumNode('generate_url_type')
                            ->values(array(
                                'auto',
                                UrlGeneratorInterface::ABSOLUTE_PATH,
                                UrlGeneratorInterface::ABSOLUTE_URL,
                                UrlGeneratorInterface::NETWORK_PATH,
                                UrlGeneratorInterface::RELATIVE_PATH,
                            ))
                            ->defaultValue('auto')
                            ->info('Set what URLs to generate on invalidate/refresh Route. Auto means path if base_url is set on the default proxy client, full URL otherwise.')
                        ->end()
                    ->end()
        ;
    }

    private function addTagSection(ArrayNodeDefinition $rootNode)
    {
        $rules = $rootNode
            ->children()
                ->arrayNode('tags')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('rule')
                    ->children()
                        ->enumNode('enabled')
                            ->values(array(true, false, 'auto'))
                            ->defaultValue('auto')
                            ->info('Allows to disable the event subscriber for tag configuration and annotations when your project does not use the annotations. Enabled by default if you configured the cache manager.')
                        ->end()
                        ->scalarNode('header')
                            ->defaultValue('X-Cache-Tags')
                            ->info('HTTP header that contains cache tags')
                        ->end()
                        ->arrayNode('rules')
                            ->prototype('array')
                                ->fixXmlConfig('tag')
                                ->fixXmlConfig('tag_expression')
                                ->validate()
                                    ->ifTrue(function ($v) {return !empty($v['tag_expressions']) && !class_exists('Symfony\Component\ExpressionLanguage\ExpressionLanguage');})
                                    ->thenInvalid('Configured a tag_expression but ExpressionLanugage is not available')
                                ->end()
                                ->children();

        $this->addMatch($rules);

        $rules
            ->arrayNode('tags')
                ->prototype('scalar')
                ->info('Tags to add to the response on safe requests, to invalidate on unsafe requests')
            ->end()->end()
            ->arrayNode('tag_expressions')
                ->prototype('scalar')
                ->info('Tags to add to the response on safe requests, to invalidate on unsafe requests')
            ->end()
        ;
    }

    private function addInvalidationSection(ArrayNodeDefinition $rootNode)
    {
        $rules = $rootNode
            ->children()
                ->arrayNode('invalidation')
                    ->fixXmlConfig('rule')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('enabled')
                            ->values(array(true, false, 'auto'))
                            ->defaultValue('auto')
                            ->info('Allows to disable the listener for invalidation. Enabled by default if the cache manager is configured. When disabled, the cache manager is no longer flushed automatically.')
                        ->end()
                        ->arrayNode('rules')
                            ->info('Set what requests should invalidate which target routes.')
                            ->prototype('array')
                                ->fixXmlConfig('route')
                                ->children();

        $this->addMatch($rules);
        $rules
            ->arrayNode('routes')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->info('Target routes to invalidate when request is matched')
                ->prototype('array')
                    ->children()
                        ->booleanNode('ignore_extra_params')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * User context main section.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addUserContextListenerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('user_context')
                    ->info('Listener that returns the request for the user context hash as early as possible.')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->fixXmlConfig('user_identifier_header')
                    ->children()
                        ->arrayNode('match')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('matcher_service')
                                    ->defaultValue('fos_http_cache.user_context.request_matcher')
                                    ->info('Service id of a request matcher that tells whether the request is a context hash request.')
                                ->end()
                                ->scalarNode('accept')
                                    ->defaultValue('application/vnd.fos.user-context-hash')
                                    ->info('Specify the accept HTTP header used for context hash requests.')
                                ->end()
                                ->scalarNode('method')
                                    ->defaultNull()
                                    ->info('Specify the HTTP method used for context hash requests.')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('hash_cache_ttl')
                            ->defaultValue(0)
                            ->info('Cache the response for the hash for the specified number of seconds. Setting this to 0 will not cache those responses at all.')
                        ->end()
                        ->arrayNode('user_identifier_headers')
                            ->prototype('scalar')->end()
                            ->defaultValue(array('Cookie', 'Authorization'))
                            ->info('List of headers that contains the unique identifier for the user in the hash request.')
                        ->end()
                        ->scalarNode('user_hash_header')
                            ->defaultValue('X-User-Context-Hash')
                            ->info('Name of the header that contains the hash information for the context.')
                        ->end()
                        ->booleanNode('role_provider')
                            ->defaultFalse()
                            ->info('Whether to enable a provider that automatically adds all roles of the current user to the context.')
                        ->end()
                        ->arrayNode('logout_handler')
                            ->addDefaultsIfNotSet()
                            ->canBeEnabled()
                            ->children()
                                ->enumNode('enabled')
                                    ->values(array(true, false, 'auto'))
                                    ->defaultValue('auto')
                                    ->info('Whether to enable the user context logout handler.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addFlashMessageSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('flash_message')
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
                    ->end()
                ->end()
            ->end();
    }

    private function addDebugSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('debug')
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->booleanNode('enabled')
                        ->defaultValue($this->debug)
                        ->info('Whether to send a debug header with the response to trigger a caching proxy to send debug information. If not set, defaults to kernel.debug.')
                    ->end()
                    ->scalarNode('header')
                        ->defaultValue('X-Cache-Debug')
                        ->info('The header to send if debug is true.')
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
