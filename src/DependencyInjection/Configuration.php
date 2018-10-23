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

use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This class contains the configuration information for the bundle.
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
     * @param bool $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('fos_http_cache');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('fos_http_cache');
        }

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
                ->ifTrue(function ($v) {
                    return $v['tags']['enabled'] && !$v['cache_manager']['enabled'];
                })
                ->then(function ($v) {
                    if ('auto' === $v['tags']['enabled']) {
                        $v['tags']['enabled'] = false;

                        return $v;
                    }

                    throw new InvalidConfigurationException('You need to configure a proxy_client to get the cache_manager needed for tag handling.');
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    return $v['invalidation']['enabled'] && !$v['cache_manager']['enabled'];
                })
                ->then(function ($v) {
                    if ('auto' === $v['invalidation']['enabled']) {
                        $v['invalidation']['enabled'] = false;

                        return $v;
                    }

                    throw new InvalidConfigurationException('You need to configure a proxy_client to get the cache_manager needed for invalidation handling.');
                })
            ->end()
            ->validate()
                ->ifTrue(
                    function ($v) {
                        return false !== $v['user_context']['logout_handler']['enabled'];
                    }
                )
                ->then(function ($v) {
                    if (isset($v['cache_manager']['custom_proxy_client'])) {
                        $v['user_context']['logout_handler']['enabled'] = true;

                        return $v;
                    }

                    if (isset($v['proxy_client']['default'])
                        && in_array($v['proxy_client']['default'], ['varnish', 'symfony', 'noop'])
                    ) {
                        $v['user_context']['logout_handler']['enabled'] = true;

                        return $v;
                    }
                    if (isset($v['proxy_client']['varnish'])
                        || isset($v['proxy_client']['symfony'])
                        || isset($v['proxy_client']['noop'])
                    ) {
                        $v['user_context']['logout_handler']['enabled'] = true;

                        return $v;
                    }

                    if ('auto' === $v['user_context']['logout_handler']['enabled']) {
                        $v['user_context']['logout_handler']['enabled'] = false;

                        return $v;
                    }

                    throw new InvalidConfigurationException('To enable the user context logout handler, you need to configure a ban capable proxy_client.');
                })
            ->end()
            // Determine the default tags header for the varnish client, depending on whether we use BAN or xkey
            ->validate()
                ->ifTrue(
                    function ($v) {
                        return
                            array_key_exists('proxy_client', $v)
                            && array_key_exists('varnish', $v['proxy_client'])
                            && empty($v['proxy_client']['varnish']['tags_header'])
                        ;
                    }
                )
                ->then(function ($v) {
                    $v['proxy_client']['varnish']['tags_header'] =
                        (Varnish::TAG_XKEY === $v['proxy_client']['varnish']['tag_mode'])
                        ? Varnish::DEFAULT_HTTP_HEADER_CACHE_XKEY
                        : Varnish::DEFAULT_HTTP_HEADER_CACHE_TAGS;

                    return $v;
                })
            ->end()
            // Determine the default tag response header, depending on whether we use BAN or xkey
            ->validate()
                ->ifTrue(
                    function ($v) {
                        return empty($v['tags']['response_header']);
                    }
                )
                ->then(function ($v) {
                    $v['tags']['response_header'] = $this->isVarnishXkey($v) ? 'xkey' : TagHeaderFormatter::DEFAULT_HEADER_NAME;

                    return $v;
                })
            ->end()
            // Determine the default separator for the tags header, depending on whether we use BAN or xkey
            ->validate()
                ->ifTrue(
                    function ($v) {
                        return empty($v['tags']['separator']);
                    }
                )
                ->then(function ($v) {
                    $v['tags']['separator'] = $this->isVarnishXkey($v) ? ' ' : ',';

                    return $v;
                })
        ;

        $this->addCacheableResponseSection($rootNode);
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

    private function isVarnishXkey(array $v): bool
    {
        return array_key_exists('proxy_client', $v)
            && array_key_exists('varnish', $v['proxy_client'])
            && Varnish::TAG_XKEY === $v['proxy_client']['varnish']['tag_mode']
        ;
    }

    private function addCacheableResponseSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('cacheable')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('response')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('additional_status')
                                    ->prototype('scalar')->end()
                                    ->info('Additional response HTTP status codes that will be considered cacheable.')
                                ->end()
                                ->scalarNode('expression')
                                    ->defaultNull()
                                    ->info('Expression to decide whether response is cacheable. Replaces the default status codes.')
                            ->end()
                        ->end()

                        ->validate()
                            ->ifTrue(function ($v) {
                                return !empty($v['additional_status']) && !empty($v['expression']);
                            })
                            ->thenInvalid('You may not set both additional_status and expression.')
                        ->end()
                    ->end()
                ->end()
            ->end();
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

        $this->addMatch($rules, true);
        $rules
            ->arrayNode('headers')
                ->isRequired()
                // todo validate there is some header defined
                ->children()
                    ->enumNode('overwrite')
                        ->info('Whether to overwrite cache headers for this rule, defaults to the cache_control.defaults.overwrite setting')
                        ->values(['default', true, false])
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
                            ->booleanNode('no_store')->end()
                            ->scalarNode('stale_if_error')->end()
                            ->scalarNode('stale_while_revalidate')->end()
                        ->end()
                    ->end()
                    ->enumNode('etag')
                        ->defaultValue(false)
                        ->treatTrueLike('strong')
                        ->info('Set a simple ETag which is just the md5 hash of the response body. '.
                               'You can specify which type of ETag you want by passing "strong" or "weak".')
                        ->values(['weak', 'strong', false])
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
                        ->beforeNormalization()->ifString()->then(function ($v) {
                            return preg_split('/\s*,\s*/', $v);
                        })->end()
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
     * @param bool        $matchResponse whether to also add fields to match response
     */
    private function addMatch(NodeBuilder $rules, $matchResponse = false)
    {
        $match = $rules
            ->arrayNode('match')
                ->cannotBeOverwritten()
                ->isRequired()
                ->fixXmlConfig('method')
                ->fixXmlConfig('ip')
                ->fixXmlConfig('attribute')
                ->validate()
                    ->ifTrue(function ($v) {
                        return !empty($v['additional_response_status']) && !empty($v['match_response']);
                    })
                    ->thenInvalid('You may not set both additional_response_status and match_response.')
                ->end()
                ->children()
                    ->scalarNode('path')
                        ->defaultNull()
                        ->info('Request path.')
                    ->end()
                    ->scalarNode('query_string')
                        ->defaultNull()
                        ->info('Request query string.')
                    ->end()
                    ->scalarNode('host')
                        ->defaultNull()
                        ->info('Request host name.')
                    ->end()
                    ->arrayNode('methods')
                        ->beforeNormalization()->ifString()->then(function ($v) {
                            return preg_split('/\s*,\s*/', $v);
                        })->end()
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->info('Request HTTP methods.')
                    ->end()
                    ->arrayNode('ips')
                        ->beforeNormalization()->ifString()->then(function ($v) {
                            return preg_split('/\s*,\s*/', $v);
                        })->end()
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->info('List of client IPs.')
                    ->end()
                    ->arrayNode('attributes')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->info('Regular expressions on request attributes.')
                    ->end()
        ;
        if ($matchResponse) {
            $match
                ->arrayNode('additional_response_status')
                    ->prototype('scalar')->end()
                    ->info('Additional response HTTP status codes that will match. Replaces cacheable configuration.')
                ->end()
                ->scalarNode('match_response')
                    ->defaultNull()
                    ->info('Expression to decide whether response should be matched. Replaces cacheable configuration.')
                ->end()
            ;
        }
    }

    private function addProxyClientSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('proxy_client')
                    ->children()
                        ->enumNode('default')
                            ->values(['varnish', 'nginx', 'symfony', 'noop'])
                            ->info('If you configure more than one proxy client, you need to specify which client is the default.')
                        ->end()
                        ->arrayNode('varnish')
                            ->fixXmlConfig('default_ban_header')
                            ->validate()
                                ->always(function ($v) {
                                    if (!count($v['default_ban_headers'])) {
                                        unset($v['default_ban_headers']);
                                    }

                                    return $v;
                                })
                            ->end()
                            ->children()
                                ->scalarNode('tags_header')
                                    ->info('HTTP header to use when sending tag invalidation requests to Varnish')
                                ->end()
                                ->scalarNode('header_length')
                                    ->info('Maximum header length when invalidating tags. If there are more tags to invalidate than fit into the header, the invalidation request is split into several requests.')
                                ->end()
                                ->arrayNode('default_ban_headers')
                                    ->useAttributeAsKey('name')
                                    ->info('Map of additional headers to include in each ban request.')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->enumNode('tag_mode')
                                    ->info('If you can enable the xkey module in Varnish, use the purgekeys mode for more efficient tag handling')
                                    ->values(['ban', 'purgekeys'])
                                    ->defaultValue('ban')
                                ->end()
                                ->append($this->getHttpDispatcherNode())
                            ->end()
                        ->end()

                        ->arrayNode('nginx')
                            ->children()
                                ->scalarNode('purge_location')
                                    ->defaultValue(false)
                                    ->info('Path to trigger the purge on Nginx for different location purge.')
                                ->end()
                                ->append($this->getHttpDispatcherNode())
                            ->end()
                        ->end()

                        ->arrayNode('symfony')
                            ->children()
                                ->scalarNode('tags_header')
                                    ->defaultValue(PurgeTagsListener::DEFAULT_TAGS_HEADER)
                                    ->info('HTTP header to use when sending tag invalidation requests to Symfony HttpCache')
                                ->end()
                                ->scalarNode('tags_method')
                                    ->defaultValue(PurgeTagsListener::DEFAULT_TAGS_METHOD)
                                    ->info('HTTP method for sending tag invalidation requests to Symfony HttpCache')
                                ->end()
                                ->scalarNode('header_length')
                                    ->info('Maximum header length when invalidating tags. If there are more tags to invalidate than fit into the header, the invalidation request is split into several requests.')
                                ->end()
                                ->scalarNode('purge_method')
                                    ->defaultValue(PurgeListener::DEFAULT_PURGE_METHOD)
                                    ->info('HTTP method to use when sending purge requests to Symfony HttpCache')
                                ->end()
                                ->booleanNode('use_kernel_dispatcher')
                                    ->defaultFalse()
                                    ->info('Dispatches invalidation requests to the kernel directly instead of executing real HTTP requests. Requires special kernel setup! Refer to the documentation for more information.')
                                ->end()
                                ->append($this->getHttpDispatcherNode())
                            ->end()
                        ->end()

                        ->booleanNode('noop')->end()
                    ->end()
                    ->validate()
                        ->always()
                        ->then(function ($config) {
                            foreach ($config as $proxyName => $proxyConfig) {
                                $serversConfigured = isset($proxyConfig['http']) && isset($proxyConfig['http']['servers']) && \is_array($proxyConfig['http']['servers']);

                                if (!\in_array($proxyName, ['noop', 'default', 'symfony'])) {
                                    if (!$serversConfigured) {
                                        throw new \InvalidArgumentException(sprintf('The "http.servers" section must be defined for the proxy "%s"', $proxyName));
                                    }

                                    return $config;
                                }

                                if ('symfony' === $proxyName) {
                                    if (!$serversConfigured && false === $proxyConfig['use_kernel_dispatcher']) {
                                        throw new \InvalidArgumentException('Either configure the "http.servers" section or enable "proxy_client.symfony.use_kernel_dispatcher"');
                                    }
                                }
                            }

                            return $config;
                        })
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Get the configuration node for a HTTP dispatcher in a proxy client.
     *
     * @return NodeDefinition
     */
    private function getHttpDispatcherNode()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('http');
            $node = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $node = $treeBuilder->root('http');
        }

        $node
            ->fixXmlConfig('server')
            ->children()
                ->arrayNode('servers')
                    ->info('Addresses of the hosts the caching proxy is running on. May be hostname or ip, and with :port if not the default port 80.')
                    ->useAttributeAsKey('name')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('base_url')
                    ->defaultNull()
                    ->info('Default host name and optional path for path based invalidation.')
                ->end()
                ->scalarNode('http_client')
                    ->defaultNull()
                    ->info('Httplug async client service name to use for sending the requests.')
                ->end()
            ->end()
        ;

        return $node;
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
                                    ->values(['varnish', 'nginx'])
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
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Cache manager main section.
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
                            ->values([true, false, 'auto'])
                            ->defaultValue('auto')
                            ->info('Allows to disable the invalidation manager. Enabled by default if you configure a proxy client.')
                        ->end()
                        ->scalarNode('custom_proxy_client')
                            ->info('Service name of a custom proxy client to use. With a custom client, generate_url_type defaults to ABSOLUTE_URL and tag support needs to be explicitly enabled. If no custom proxy client is specified, the first proxy client you configured is used.')
                            ->cannotBeEmpty()
                        ->end()
                        ->enumNode('generate_url_type')
                            ->values([
                                'auto',
                                UrlGeneratorInterface::ABSOLUTE_PATH,
                                UrlGeneratorInterface::ABSOLUTE_URL,
                                UrlGeneratorInterface::NETWORK_PATH,
                                UrlGeneratorInterface::RELATIVE_PATH,
                            ])
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
                            ->values([true, false, 'auto'])
                            ->defaultValue('auto')
                            ->info('Allows to disable tag support. Enabled by default if you configured the cache manager and have a proxy client that supports tagging.')
                        ->end()
                        ->arrayNode('annotations')
                            ->info('Annotations require the FrameworkExtraBundle. Because we can not detect whether annotations are used when the FrameworkExtraBundle is not available, this option must be set to false explicitly if the application does not use annotations.')
                            ->canBeDisabled()
                        ->end()
                        ->booleanNode('strict')->defaultFalse()->end()
                        ->scalarNode('expression_language')
                            ->defaultNull()
                            ->info('Service name of a custom ExpressionLanugage to use.')
                        ->end()
                        ->scalarNode('response_header')
                            ->defaultNull()
                            ->info('HTTP header that contains cache tags. Defaults to xkey-softpurge for Varnish xkey or X-Cache-Tags otherwise')
                        ->end()
                        ->scalarNode('separator')
                            ->defaultNull()
                            ->info('Character(s) to use to separate multiple tags. Defaults to " " for Varnish xkey or "," otherwise')
                        ->end()
                        ->scalarNode('max_header_value_length')
                            ->defaultNull()
                            ->info('If configured the tag header value will be split into multiple response headers of the same name (see "response_header" configuration key) that all do not exceed the configured "max_header_value_length" (recommended is 4KB = 4096) - configure in bytes.')
                        ->end()
                        ->arrayNode('rules')
                            ->prototype('array')
                                ->fixXmlConfig('tag')
                                ->fixXmlConfig('tag_expression')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !empty($v['tag_expressions']) && !class_exists(ExpressionLanguage::class);
                                    })
                                    ->thenInvalid('Configured a tag_expression but ExpressionLanugage is not available')
                                ->end()
                                ->children()
                        ;
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
                            ->values([true, false, 'auto'])
                            ->defaultValue('auto')
                            ->info('Allows to disable the listener for invalidation. Enabled by default if the cache manager is configured. When disabled, the cache manager is no longer flushed automatically.')
                        ->end()
                        ->scalarNode('expression_language')
                            ->defaultNull()
                            ->info('Service name of a custom ExpressionLanugage to use.')
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
                        ->booleanNode('always_vary_on_context_hash')
                            ->defaultTrue()
                            ->info('Whether to always add the user context hash header name in the response Vary header.')
                        ->end()
                        ->arrayNode('user_identifier_headers')
                            ->prototype('scalar')->end()
                            ->defaultValue(['Cookie', 'Authorization'])
                            ->info('List of headers that contain the unique identifier for the user in the hash request.')
                        ->end()
                        ->scalarNode('session_name_prefix')
                            ->defaultValue(false)
                            ->info('Prefix for session cookies. Must match your PHP session configuration. Set to false to ignore the session in user context.')
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
                                    ->values([true, false, 'auto'])
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
