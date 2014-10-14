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

use FOS\HttpCacheBundle\DependencyInjection\Compiler\HashGeneratorPass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * {@inheritdoc}
 */
class FOSHttpCacheExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if ($config['debug']['enabled'] || (!empty($config['cache_control']))) {
            $debugHeader = $config['debug']['enabled'] ? $config['debug']['header'] : false;
            $container->setParameter($this->getAlias().'.debug_header', $debugHeader);
            $loader->load('cache_control_listener.xml');
        }

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
            $loader->load('cache_manager.xml');
        }

        if ($config['tags']['enabled']) {
            // true or auto
            $loader->load('tag_listener.xml');
            if (!empty($config['tags']['rules'])) {
                $this->loadTagRules($container, $config['tags']['rules']);
            }

            $tagsHeader = $config['tags']['header'];
            $container->getDefinition($this->getAlias().'.cache_manager')
                ->addMethodCall('setTagsHeader', array($tagsHeader))
            ;
        }

        if ($config['invalidation']['enabled']) {
            $loader->load('invalidation_listener.xml');
            if (!empty($config['invalidation']['rules'])) {
                $this->loadInvalidatorRules($container, $config['invalidation']['rules']);
            }
        }

        if ($config['user_context']['enabled']) {
            $this->loadUserContext($container, $loader, $config['user_context']);
        }

        if (!empty($config['flash_message']) && $config['flash_message']['enabled']) {
            $container->setParameter($this->getAlias().'.event_listener.flash_message.options', $config['flash_message']);

            $loader->load('flash_message.xml');
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @throws InvalidConfigurationException
     */
    private function loadCacheControl(ContainerBuilder $container, array $config)
    {
        $controlDefinition = $container->getDefinition($this->getAlias() . '.event_listener.cache_control');

        foreach ($config['rules'] as $rule) {
            $ruleMatcher = $this->parseRuleMatcher($container, $rule['match']);

            if ('default' === $rule['headers']['overwrite']) {
                $rule['headers']['overwrite'] = $config['defaults']['overwrite'];
            }

            $controlDefinition->addMethodCall('addRule', array($ruleMatcher, $rule['headers']));
        }
    }

    private function parseRuleMatcher(ContainerBuilder $container, array $match)
    {
        $match['ips'] = (empty($match['ips'])) ? null : $match['ips'];

        $requestMatcher = $this->createRequestMatcher(
            $container,
            $match['path'],
            $match['host'],
            $match['methods'],
            $match['ips'],
            $match['attributes']
        );

        $extraCriteria = array();
        foreach (array('additional_cacheable_status', 'match_response') as $extra) {
            if (isset($match[$extra])) {
                $extraCriteria[$extra] = $match[$extra];
            }
        }

        return $this->createRuleMatcher(
            $container,
            $requestMatcher,
            $extraCriteria
        );
    }

    private function createRuleMatcher(ContainerBuilder $container, Reference $requestMatcher, array $extraCriteria)
    {
        $arguments = array((string) $requestMatcher, $extraCriteria);
        $serialized = serialize($arguments);
        $id = $this->getAlias() . '.rule_matcher.' . md5($serialized) . sha1($serialized);

        if (!$container->hasDefinition($id)) {
            $container
                ->setDefinition($id, new DefinitionDecorator($this->getAlias().'.rule_matcher'))
                ->replaceArgument(0, $requestMatcher)
                ->replaceArgument(1, $extraCriteria)
            ;
        }

        return new Reference($id);
    }

    private function loadUserContext(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('user_context.xml');

        $container->getDefinition($this->getAlias().'.user_context.request_matcher')
            ->replaceArgument(0, $config['match']['accept'])
            ->replaceArgument(1, $config['match']['method']);

        $container->getDefinition($this->getAlias().'.event_listener.user_context')
            ->replaceArgument(0, new Reference($config['match']['matcher_service']))
            ->replaceArgument(2, $config['user_identifier_headers'])
            ->replaceArgument(3, $config['user_hash_header'])
            ->replaceArgument(4, $config['hash_cache_ttl']);

        $container->getDefinition($this->getAlias().'.user_context.logout_handler')
            ->replaceArgument(1, $config['user_identifier_headers'])
            ->replaceArgument(2, $config['match']['accept']);

        if ($config['role_provider']) {
            $container->getDefinition($this->getAlias().'.user_context.role_provider')
                ->addTag(HashGeneratorPass::TAG_NAME)
                ->setAbstract(false);
        }
    }

    private function createRequestMatcher(ContainerBuilder $container, $path = null, $host = null, $methods = null, $ips = null, array $attributes = array())
    {
        $arguments = array($path, $host, $methods, $ips, $attributes);
        $serialized = serialize($arguments);
        $id = $this->getAlias().'.request_matcher.'.md5($serialized).sha1($serialized);

        if (!$container->hasDefinition($id)) {
            $container
                ->setDefinition($id, new DefinitionDecorator($this->getAlias().'.request_matcher'))
                ->setArguments($arguments)
            ;
        }

        return new Reference($id);
    }

    private function loadProxyClient(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        if (isset($config['varnish'])) {
            $this->loadVarnish($container, $loader, $config['varnish']);
        }
        if (isset($config['nginx'])) {
            $this->loadNginx($container, $loader, $config['nginx']);
        }

        $container->setAlias(
            $this->getAlias() . '.default_proxy_client',
            $this->getAlias() . '.proxy_client.' . $this->getDefault($config)
        );
    }

    private function loadVarnish(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('varnish.xml');
        foreach ($config['servers'] as $url) {
            $this->validateUrl($url, 'Not a valid Varnish server address: "%s"');
        }
        if (!empty($config['base_url'])) {
            $baseUrl = $this->prefixSchema($config['base_url'], 'Not a valid base path: "%s"');
            $this->validateUrl($baseUrl, 'Not a valid base path: "%s"');
        } else {
            $baseUrl = null;
        }
        $container->setParameter($this->getAlias() . '.proxy_client.varnish.servers', $config['servers']);
        $container->setParameter($this->getAlias() . '.proxy_client.varnish.base_url', $baseUrl);
        if ($config['guzzle_client']) {
            $container->getDefinition($this->getAlias() . '.proxy_client.varnish')
                ->addArgument(
                    new Reference($config['guzzle_client'])
                )
            ;
        }
    }

    private function loadNginx(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('nginx.xml');
        foreach ($config['servers'] as $url) {
            $this->validateUrl($url, 'Not a valid Nginx server address: "%s"');
        }
        if (!empty($config['base_url'])) {
            $baseUrl = $this->prefixSchema($config['base_url'], 'Not a valid base path: "%s"');
        } else {
            $baseUrl = null;
        }
        $container->setParameter($this->getAlias() . '.proxy_client.nginx.servers', $config['servers']);
        $container->setParameter($this->getAlias() . '.proxy_client.nginx.base_url', $baseUrl);
        $container->setParameter($this->getAlias() . '.proxy_client.nginx.purge_location', $config['purge_location']);
    }

    private function loadTest(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $container->setParameter($this->getAlias() . '.test.cache_header', $config['cache_header']);

        if ($config['proxy_server']) {
            $this->loadProxyServer($container, $loader, $config['proxy_server']);
        }

        if (isset($config['client']['varnish']['enabled'])
            || isset($config['client']['nginx']['enabled'])) {
            $loader->load('test_client.xml');

            if ($config['client']['varnish']['enabled']) {
                $container->getDefinition($this->getAlias() . '.test.client.varnish')
                    ->setAbstract(false);
            }

            if ($config['client']['nginx']['enabled']) {
                $container->getDefinition($this->getAlias() . '.test.client.nginx')
                    ->setAbstract(false);
            }

            $container->setAlias(
                $this->getAlias() . '.test.default_client',
                $this->getAlias() . '.test.client.' . $this->getDefault($config['client'])
            );
        }
    }

    private function loadProxyServer(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        if (isset($config['varnish'])) {
            $this->loadVarnishProxyServer($container, $loader, $config['varnish']);
        }

        if (isset($config['nginx'])) {
            $this->loadNginxProxyServer($container, $loader, $config['varnish']);
        }

        $container->setAlias(
            $this->getAlias() . '.test.default_proxy_server',
            $this->getAlias() . '.test.proxy_server.' . $this->getDefault($config)
        );
    }

    private function loadVarnishProxyServer(ContainerBuilder $container, XmlFileLoader $loader, $config)
    {
        $loader->load('varnish_proxy.xml');
        foreach ($config as $key => $value) {
            $container->setParameter(
                $this->getAlias() . '.test.proxy_server.varnish.' . $key,
                $value
            );
        }
    }

    private function loadNginxProxyServer(ContainerBuilder $container, XmlFileLoader $loader, $config)
    {
        $loader->load('nginx_proxy.xml');
        foreach ($config as $key => $value) {
            $container->setParameter(
                $this->getAlias() . '.test.proxy_server.nginx.' . $key,
                $value
            );
        }
    }

    private function loadTagRules(ContainerBuilder $container, array $config)
    {
        $tagDefinition = $container->getDefinition($this->getAlias() . '.event_listener.tag');

        foreach ($config as $rule) {
            $ruleMatcher = $this->parseRuleMatcher($container, $rule['match']);

            $tags = array(
                'tags' => $rule['tags'],
                'expressions' => $rule['tag_expressions'],
            );

            $tagDefinition->addMethodCall('addRule', array($ruleMatcher, $tags));
        }
    }

    private function loadInvalidatorRules(ContainerBuilder $container, array $config)
    {
        $tagDefinition = $container->getDefinition($this->getAlias() . '.event_listener.invalidation');

        foreach ($config as $rule) {
            $ruleMatcher = $this->parseRuleMatcher($container, $rule['match']);
            $tagDefinition->addMethodCall('addRule', array($ruleMatcher, $rule['routes']));
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

    private function getDefault(array $config)
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
    }
}
