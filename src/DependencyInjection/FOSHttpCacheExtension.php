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

use AsyncAws\CloudFront\CloudFrontClient;
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use FOS\HttpCache\ProxyClient\ProxyClient;
use FOS\HttpCache\SymfonyCache\KernelDispatcher;
use FOS\HttpCache\TagHeaderFormatter\MaxHeaderValueLengthFormatter;
use FOS\HttpCacheBundle\DependencyInjection\Compiler\HashGeneratorPass;
use FOS\HttpCacheBundle\Http\ResponseMatcher\ExpressionResponseMatcher;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * {@inheritdoc}
 */
class FOSHttpCacheExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('matcher.xml');

        if ($config['debug']['enabled'] || (!empty($config['cache_control']))) {
            $debugHeader = $config['debug']['enabled'] ? $config['debug']['header'] : false;
            $container->setParameter('fos_http_cache.debug_header', $debugHeader);
            $loader->load('cache_control_listener.xml');
        }

        $this->loadCacheable($container, $config['cacheable']);

        if (!empty($config['cache_control'])) {
            $this->loadCacheControl($container, $config['cache_control']);
        }

        if (isset($config['proxy_client'])) {
            $this->loadProxyClient($container, $loader, $config['proxy_client']);
        }

        if (isset($config['test'])) {
            $this->loadTest($container, $loader, $config['test']);
        }

        if ($config['cache_manager']['enabled']) {
            if (array_key_exists('custom_proxy_client', $config['cache_manager'])) {
                // overwrite the previously set alias, if a proxy client was also configured
                $container->setAlias(
                    'fos_http_cache.default_proxy_client',
                    $config['cache_manager']['custom_proxy_client']
                );
            }
            if ('auto' === $config['cache_manager']['generate_url_type']) {
                if (array_key_exists('custom_proxy_client', $config['cache_manager'])) {
                    $generateUrlType = UrlGeneratorInterface::ABSOLUTE_URL;
                } else {
                    $defaultClient = $this->getDefaultProxyClient($config['proxy_client']);
                    if ('noop' !== $defaultClient
                        && array_key_exists('base_url', $config['proxy_client'][$defaultClient])) {
                        $generateUrlType = UrlGeneratorInterface::ABSOLUTE_PATH;
                    } elseif ('cloudfront' === $defaultClient) {
                        $generateUrlType = UrlGeneratorInterface::ABSOLUTE_PATH;
                    } else {
                        $generateUrlType = UrlGeneratorInterface::ABSOLUTE_URL;
                    }
                }
            } else {
                $generateUrlType = $config['cache_manager']['generate_url_type'];
            }
            $container->setParameter('fos_http_cache.cache_manager.generate_url_type', $generateUrlType);
            $loader->load('cache_manager.xml');
            if (class_exists(Application::class)) {
                $loader->load('cache_manager_commands.xml');
            }
        }

        if ($config['tags']['enabled']) {
            $this->loadCacheTagging(
                $container,
                $loader,
                $config['tags'],
                array_key_exists('proxy_client', $config)
                    ? $this->getDefaultProxyClient($config['proxy_client'])
                    : 'custom'
            );
        } else {
            $container->setParameter('fos_http_cache.compiler_pass.tag_annotations', false);
        }

        if ($config['invalidation']['enabled']) {
            $loader->load('invalidation_listener.xml');

            if (!empty($config['invalidation']['expression_language'])) {
                $container->setAlias(
                    'fos_http_cache.invalidation.expression_language',
                    $config['invalidation']['expression_language']
                );
            }

            if (!empty($config['invalidation']['rules'])) {
                $this->loadInvalidatorRules($container, $config['invalidation']['rules']);
            }
        }

        if ($config['user_context']['enabled']) {
            $this->loadUserContext($container, $loader, $config['user_context']);
        }

        if (!empty($config['flash_message']) && $config['flash_message']['enabled']) {
            unset($config['flash_message']['enabled']);
            $container->setParameter('fos_http_cache.event_listener.flash_message.options', $config['flash_message']);

            $loader->load('flash_message.xml');
        }

        if (\PHP_VERSION_ID >= 80000) {
            $loader->load('php8_attributes.xml');
        }
    }

    private function loadCacheable(ContainerBuilder $container, array $config)
    {
        $definition = $container->getDefinition('fos_http_cache.response_matcher.cacheable');

        // Change CacheableResponseMatcher to ExpressionResponseMatcher
        if ($config['response']['expression']) {
            $definition->setClass(ExpressionResponseMatcher::class)
                ->setArguments([$config['response']['expression']]);
        } else {
            $container->setParameter(
                'fos_http_cache.cacheable.response.additional_status',
                $config['response']['additional_status']
            );
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function loadCacheControl(ContainerBuilder $container, array $config)
    {
        $controlDefinition = $container->getDefinition('fos_http_cache.event_listener.cache_control');

        foreach ($config['rules'] as $rule) {
            $ruleMatcher = $this->parseRuleMatcher($container, $rule['match']);

            if ('default' === $rule['headers']['overwrite']) {
                $rule['headers']['overwrite'] = $config['defaults']['overwrite'];
            }

            $controlDefinition->addMethodCall('addRule', [$ruleMatcher, $rule['headers']]);
        }
    }

    /**
     * Parse one cache control rule match configuration.
     *
     * @param array $match Request and response match criteria
     *
     * @return Reference pointing to a rule matcher service
     */
    private function parseRuleMatcher(ContainerBuilder $container, array $match)
    {
        $requestMatcher = $this->parseRequestMatcher($container, $match);
        $responseMatcher = $this->parseResponseMatcher($container, $match);

        $signature = serialize([(string) $requestMatcher, (string) $responseMatcher]);
        $id = 'fos_http_cache.cache_control.rule_matcher.'.md5($signature);

        if ($container->hasDefinition($id)) {
            throw new InvalidConfigurationException('Duplicate match criteria. Would be hidden by a previous rule. match: '.json_encode($match));
        }

        $container
            ->setDefinition($id, new ChildDefinition('fos_http_cache.rule_matcher'))
            ->replaceArgument(0, $requestMatcher)
            ->replaceArgument(1, $responseMatcher)
        ;

        return new Reference($id);
    }

    /**
     * Used for cache control, tag and invalidation rules.
     *
     * @return Reference to the request matcher
     */
    private function parseRequestMatcher(ContainerBuilder $container, array $match)
    {
        $match['ips'] = (empty($match['ips'])) ? null : $match['ips'];

        $arguments = [
            $match['path'],
            $match['host'],
            $match['methods'],
            $match['ips'],
            $match['attributes'],
        ];
        $serialized = serialize($arguments);
        $id = 'fos_http_cache.request_matcher.'.md5($serialized).sha1($serialized);

        if (!$container->hasDefinition($id)) {
            $container
                ->setDefinition($id, new ChildDefinition('fos_http_cache.request_matcher'))
                ->setArguments($arguments)
            ;

            if (!empty($match['query_string'])) {
                $container->getDefinition($id)->addMethodCall('setQueryString', [$match['query_string']]);
            }
        }

        return new Reference($id);
    }

    /**
     * Used only for cache control rules.
     *
     * @return Reference to the correct response matcher service
     */
    private function parseResponseMatcher(ContainerBuilder $container, array $config)
    {
        if (!empty($config['additional_response_status'])) {
            $id = 'fos_http_cache.cache_control.expression.'.md5(serialize($config['additional_response_status']));
            if (!$container->hasDefinition($id)) {
                $container
                    ->setDefinition($id, new ChildDefinition('fos_http_cache.response_matcher.cache_control.cacheable_response'))
                    ->setArguments([$config['additional_response_status']])
                ;
            }
        } elseif (!empty($config['match_response'])) {
            $id = 'fos_http_cache.cache_control.match_response.'.md5($config['match_response']);
            if (!$container->hasDefinition($id)) {
                $container
                    ->setDefinition($id, new ChildDefinition('fos_http_cache.response_matcher.cache_control.expression'))
                    ->replaceArgument(0, $config['match_response'])
                ;
            }
        } else {
            $id = 'fos_http_cache.response_matcher.cacheable';
        }

        return new Reference($id);
    }

    private function loadUserContext(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $configuredUserIdentifierHeaders = array_map('strtolower', $config['user_identifier_headers']);
        $completeUserIdentifierHeaders = $configuredUserIdentifierHeaders;
        if (false !== $config['session_name_prefix'] && !in_array('cookie', $completeUserIdentifierHeaders)) {
            $completeUserIdentifierHeaders[] = 'cookie';
        }

        $loader->load('user_context.xml');
        // TODO: Remove this service file when going to version 3 of the bundle
        if (Kernel::MAJOR_VERSION >= 6) {
            $loader->load('user_context_legacy_sf6.xml');
        } else {
            $loader->load('user_context_legacy.xml');
        }

        $container->getDefinition('fos_http_cache.user_context.request_matcher')
            ->replaceArgument(0, $config['match']['accept'])
            ->replaceArgument(1, $config['match']['method']);

        $container->setParameter('fos_http_cache.event_listener.user_context.options', [
            'user_identifier_headers' => $completeUserIdentifierHeaders,
            'user_hash_header' => $config['user_hash_header'],
            'ttl' => $config['hash_cache_ttl'],
            'add_vary_on_hash' => $config['always_vary_on_context_hash'],
        ]);

        $container->getDefinition('fos_http_cache.event_listener.user_context')
            ->replaceArgument(0, new Reference($config['match']['matcher_service']))
        ;

        $options = [
            'user_identifier_headers' => $configuredUserIdentifierHeaders,
            'session_name_prefix' => $config['session_name_prefix'],
        ];
        $container->getDefinition('fos_http_cache.user_context.anonymous_request_matcher')
            ->replaceArgument(0, $options);

        if ($config['logout_handler']['enabled']) {
            $container->setAlias('security.logout.handler.session', 'fos_http_cache.user_context.session_logout_handler');
        } else {
            $container->removeDefinition('fos_http_cache.user_context.logout_handler');
            $container->removeDefinition('fos_http_cache.user_context.session_logout_handler');
            $container->removeDefinition('fos_http_cache.user_context_invalidator');
        }

        if ($config['role_provider']) {
            $container->getDefinition('fos_http_cache.user_context.role_provider')
                ->addTag(HashGeneratorPass::TAG_NAME)
                ->setAbstract(false);
        }
    }

    private function loadProxyClient(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        if (isset($config['varnish'])) {
            $this->loadVarnish($container, $loader, $config['varnish']);
        }
        if (isset($config['nginx'])) {
            $this->loadNginx($container, $loader, $config['nginx']);
        }
        if (isset($config['symfony'])) {
            $this->loadSymfony($container, $loader, $config['symfony']);
        }
        if (isset($config['cloudflare'])) {
            $this->loadCloudflare($container, $loader, $config['cloudflare']);
        }
        if (isset($config['cloudfront'])) {
            $this->loadCloudfront($container, $loader, $config['cloudfront']);
        }
        if (isset($config['fastly'])) {
            $this->loadFastly($container, $loader, $config['fastly']);
        }
        if (isset($config['noop'])) {
            $loader->load('noop.xml');
        }

        $container->setAlias(
            'fos_http_cache.default_proxy_client',
            'fos_http_cache.proxy_client.'.$this->getDefaultProxyClient($config)
        );
        $container->setAlias(
            ProxyClient::class,
            'fos_http_cache.default_proxy_client'
        );
    }

    /**
     * Define the http dispatcher service for the proxy client $name.
     *
     * @param string $serviceName
     */
    private function createHttpDispatcherDefinition(ContainerBuilder $container, array $config, $serviceName)
    {
        if (array_key_exists('servers', $config)) {
            foreach ($config['servers'] as $url) {
                $usedEnvs = [];
                $container->resolveEnvPlaceholders($url, null, $usedEnvs);
                if (0 === \count($usedEnvs)) {
                    $this->validateUrl($url, 'Not a valid Varnish server address: "%s"');
                }
            }
        }
        if (array_key_exists('servers_from_jsonenv', $config) && is_string($config['servers_from_jsonenv'])) {
            // check that the config contains an env var
            $usedEnvs = [];
            $container->resolveEnvPlaceholders($config['servers_from_jsonenv'], null, $usedEnvs);
            if (0 === \count($usedEnvs)) {
                throw new InvalidConfigurationException('Not a valid Varnish servers_from_jsonenv configuration: '.$config['servers_from_jsonenv']);
            }
            $config['servers'] = $config['servers_from_jsonenv'];
        }
        if (!empty($config['base_url'])) {
            $baseUrl = $config['base_url'];
            $usedEnvs = [];
            $container->resolveEnvPlaceholders($baseUrl, null, $usedEnvs);
            if (0 === \count($usedEnvs)) {
                $baseUrl = $this->prefixSchema($baseUrl);
                $this->validateUrl($baseUrl, 'Not a valid base path: "%s"');
            }
        } else {
            $baseUrl = null;
        }
        $httpClient = null;
        if ($config['http_client']) {
            $httpClient = new Reference($config['http_client']);
        }

        $definition = new Definition(HttpDispatcher::class, [
            $config['servers'],
            $baseUrl,
            $httpClient,
        ]);

        $container->setDefinition($serviceName, $definition);
    }

    private function loadVarnish(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $this->createHttpDispatcherDefinition($container, $config['http'], 'fos_http_cache.proxy_client.varnish.http_dispatcher');
        $options = [
            'tag_mode' => $config['tag_mode'],
            'tags_header' => $config['tags_header'],
        ];

        if (!empty($config['header_length'])) {
            $options['header_length'] = $config['header_length'];
        }
        if (!empty($config['default_ban_headers'])) {
            $options['default_ban_headers'] = $config['default_ban_headers'];
        }
        $container->setParameter('fos_http_cache.proxy_client.varnish.options', $options);

        $loader->load('varnish.xml');
    }

    private function loadNginx(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $this->createHttpDispatcherDefinition($container, $config['http'], 'fos_http_cache.proxy_client.nginx.http_dispatcher');
        $container->setParameter('fos_http_cache.proxy_client.nginx.options', [
            'purge_location' => $config['purge_location'],
        ]);
        $loader->load('nginx.xml');
    }

    private function loadSymfony(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $serviceName = 'fos_http_cache.proxy_client.symfony.http_dispatcher';

        if ($config['use_kernel_dispatcher']) {
            $definition = new Definition(KernelDispatcher::class, [
                new Reference('kernel'),
            ]);
            $container->setDefinition($serviceName, $definition);
        } else {
            $this->createHttpDispatcherDefinition($container, $config['http'], $serviceName);
        }

        $options = [
            'tags_header' => $config['tags_header'],
            'tags_method' => $config['tags_method'],
            'purge_method' => $config['purge_method'],
        ];
        if (!empty($config['header_length'])) {
            $options['header_length'] = $config['header_length'];
        }
        $container->setParameter('fos_http_cache.proxy_client.symfony.options', $options);

        $loader->load('symfony.xml');
    }

    private function loadCloudflare(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $this->createHttpDispatcherDefinition($container, $config['http'], 'fos_http_cache.proxy_client.cloudflare.http_dispatcher');
        $options = [
            'authentication_token' => $config['authentication_token'],
            'zone_identifier' => $config['zone_identifier'],
        ];

        $container->setParameter('fos_http_cache.proxy_client.cloudflare.options', $options);

        $loader->load('cloudflare.xml');
    }

    private function loadCloudfront(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        if (null !== $config['client']) {
            $container->setAlias(
                'fos_http_cache.proxy_client.cloudfront.cloudfront_client',
                $config['client']
            );
        } else {
            $container->setDefinition(
                'fos_http_cache.proxy_client.cloudfront.cloudfront_client',
                new Definition(CloudFrontClient::class, [$config['configuration']])
            );
        }

        $container->setParameter('fos_http_cache.proxy_client.cloudfront.options', [
            'distribution_id' => $config['distribution_id'],
        ]);

        $loader->load('cloudfront.xml');
    }

    private function loadFastly(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $this->createHttpDispatcherDefinition($container, $config['http'], 'fos_http_cache.proxy_client.fastly.http_dispatcher');

        $options = [
            'service_identifier' => $config['service_identifier'],
            'authentication_token' => $config['authentication_token'],
            'soft_purge' => $config['soft_purge'],
        ];

        $container->setParameter('fos_http_cache.proxy_client.fastly.options', $options);

        $loader->load('fastly.xml');
    }

    /**
     * @param array  $config Configuration section for the tags node
     * @param string $client Name of the client used with the cache manager,
     *                       "custom" when a custom client is used
     */
    private function loadCacheTagging(ContainerBuilder $container, XmlFileLoader $loader, array $config, $client)
    {
        if ('auto' === $config['enabled'] && !in_array($client, ['varnish', 'symfony', 'cloudflare', 'fastly'])) {
            $container->setParameter('fos_http_cache.compiler_pass.tag_annotations', false);

            return;
        }
        if (!in_array($client, ['varnish', 'symfony', 'cloudflare', 'custom', 'fastly', 'noop'])) {
            throw new InvalidConfigurationException(sprintf('You can not enable cache tagging with the %s client', $client));
        }

        $container->setParameter('fos_http_cache.compiler_pass.tag_annotations', $config['annotations']['enabled']);
        $container->setParameter('fos_http_cache.tag_handler.response_header', $config['response_header']);
        $container->setParameter('fos_http_cache.tag_handler.separator', $config['separator']);
        $container->setParameter('fos_http_cache.tag_handler.strict', $config['strict']);

        $loader->load('cache_tagging.xml');
        if (class_exists(Application::class)) {
            $loader->load('cache_tagging_commands.xml');
        }

        if (!empty($config['expression_language'])) {
            $container->setAlias(
                'fos_http_cache.tag_handler.expression_language',
                $config['expression_language']
            );
        }

        if (!empty($config['rules'])) {
            $this->loadTagRules($container, $config['rules']);
        }

        if (null !== $config['max_header_value_length']) {
            $container->register('fos_http_cache.tag_handler.max_header_value_length_header_formatter', MaxHeaderValueLengthFormatter::class)
                ->setDecoratedService('fos_http_cache.tag_handler.header_formatter')
                ->addArgument(new Reference('fos_http_cache.tag_handler.max_header_value_length_header_formatter.inner'))
                ->addArgument((int) $config['max_header_value_length']);
        }
    }

    private function loadTest(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $container->setParameter('fos_http_cache.test.cache_header', $config['cache_header']);

        if ($config['proxy_server']) {
            $this->loadProxyServer($container, $loader, $config['proxy_server']);
        }
    }

    private function loadProxyServer(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        if (isset($config['varnish'])) {
            $this->loadVarnishProxyServer($container, $loader, $config['varnish']);
        }

        if (isset($config['nginx'])) {
            $this->loadNginxProxyServer($container, $loader, $config['nginx']);
        }

        $container->setAlias(
            'fos_http_cache.test.default_proxy_server',
            'fos_http_cache.test.proxy_server.'.$this->getDefaultProxyClient($config)
        );
    }

    private function loadVarnishProxyServer(ContainerBuilder $container, XmlFileLoader $loader, $config)
    {
        $loader->load('varnish_proxy.xml');
        foreach ($config as $key => $value) {
            $container->setParameter(
                'fos_http_cache.test.proxy_server.varnish.'.$key,
                $value
            );
        }
    }

    private function loadNginxProxyServer(ContainerBuilder $container, XmlFileLoader $loader, $config)
    {
        $loader->load('nginx_proxy.xml');
        foreach ($config as $key => $value) {
            $container->setParameter(
                'fos_http_cache.test.proxy_server.nginx.'.$key,
                $value
            );
        }
    }

    private function loadTagRules(ContainerBuilder $container, array $config)
    {
        $tagDefinition = $container->getDefinition('fos_http_cache.event_listener.tag');

        foreach ($config as $rule) {
            $ruleMatcher = $this->parseRequestMatcher($container, $rule['match']);

            $tags = [
                'tags' => $rule['tags'],
                'expressions' => $rule['tag_expressions'],
            ];

            $tagDefinition->addMethodCall('addRule', [$ruleMatcher, $tags]);
        }
    }

    private function loadInvalidatorRules(ContainerBuilder $container, array $config)
    {
        $tagDefinition = $container->getDefinition('fos_http_cache.event_listener.invalidation');

        foreach ($config as $rule) {
            $ruleMatcher = $this->parseRequestMatcher($container, $rule['match']);
            $tagDefinition->addMethodCall('addRule', [$ruleMatcher, $rule['routes']]);
        }
    }

    private function validateUrl($url, $msg)
    {
        $prefixed = $this->prefixSchema($url);

        if (!$parts = parse_url($prefixed)) {
            throw new InvalidConfigurationException(sprintf($msg, $url));
        }
    }

    private function prefixSchema($url)
    {
        if (false === strpos($url, '://')) {
            $url = sprintf('%s://%s', 'http', $url);
        }

        return $url;
    }

    private function getDefaultProxyClient(array $config)
    {
        if (isset($config['default'])) {
            return $config['default'];
        }

        if (isset($config['varnish'])) {
            return 'varnish';
        }

        if (isset($config['nginx'])) {
            return 'nginx';
        }

        if (isset($config['symfony'])) {
            return 'symfony';
        }

        if (isset($config['cloudflare'])) {
            return 'cloudflare';
        }

        if (isset($config['cloudfront'])) {
            return 'cloudfront';
        }

        if (isset($config['fastly'])) {
            return 'fastly';
        }

        if (isset($config['noop'])) {
            return 'noop';
        }

        throw new InvalidConfigurationException('No proxy client configured');
    }
}
