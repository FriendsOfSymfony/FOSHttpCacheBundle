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

use FOS\HttpCacheBundle\DependencyInjection\Compiler\UserContextListenerPass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

/**
 * {@inheritdoc}
 */
class FOSHttpCacheExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (($config['debug']) || (!empty($config['rules']))) {
            $debugHeader = $config['debug'] ? $config['debug_header'] : false;
            $container->setParameter($this->getAlias().'.debug_header', $debugHeader);
            $loader->load('cache_control_listener.xml');
        }

        if (isset($config['proxy_client'])) {
            $this->loadProxyClient($container, $loader, $config['proxy_client']);
        }

        if ($config['cache_manager']['enabled'] && isset($config['proxy_client'])) {
            $this->loadCacheManager($container, $loader, $config['cache_manager']);
        }

        if (!empty($config['rules'])) {
            $this->loadRules($container, $config);
        }

        if ($config['user_context']['enabled']) {
            $this->loadUserContext($container, $loader, $config['user_context']);
        }

        if (!empty($config['flash_message_listener']) && $config['flash_message_listener']['enabled']) {
            $container->setParameter($this->getAlias().'.event_listener.flash_message.options', $config['flash_message_listener']);

            $loader->load('flash_message_listener.xml');
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @throws InvalidConfigurationException
     */
    private function loadRules(ContainerBuilder $container, array $config)
    {
        foreach ($config['rules'] as $rule) {
            $match = $rule['match'];

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
            $ruleMatcher = $this->createRuleMatcher(
                $container,
                $requestMatcher,
                $extraCriteria
            );

            $tags = array(
                'tags' => $rule['tags'],
                'expressions' => $rule['tag_expressions'],
            );
            if (count($tags['tags']) || count($tags['expressions'])) {
                if (!$container->hasDefinition($this->getAlias() . '.event_listener.tag')) {
                    throw new InvalidConfigurationException('To configure tags, you need to have the tag event listener enabled, requiring symfony/expression-language');
                }

                $container
                    ->getDefinition($this->getAlias() . '.event_listener.tag')
                    ->addMethodCall('addRule', array($ruleMatcher, $tags))
                ;
            }

            if (isset($rule['headers'])) {
                $container
                    ->getDefinition($this->getAlias() . '.event_listener.cache_control')
                    ->addMethodCall('addRule', array($ruleMatcher, $rule['headers']))
                ;
            }
        }
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

        if ($config['role_provider']) {
            $container->getDefinition($this->getAlias().'.user_context.role_provider')
                ->addTag(UserContextListenerPass::TAG_NAME)
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
        $default = empty($config['default']) ? false : $config['default'];
        if (isset($config['varnish'])) {
            $this->loadVarnish($container, $loader, $config['varnish']);
            if (!$default) {
                $default = 'varnish';
            }
        }
        if (isset($config['nginx'])) {
            $this->loadNginx($container, $loader, $config['nginx']);
            if (!$default) {
                $default = 'nginx';
            }
        }

        $container->setAlias($this->getAlias() . '.default_proxy_client', $this->getAlias() . '.proxy_client.' . $default);
    }

    private function loadVarnish(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('varnish.xml');
        foreach ($config['servers'] as $url) {
            $this->validateUrl($url, 'Not a valid varnish server address: "%s"');
        }
        if (!empty($config['base_url'])) {
            $this->validateUrl($config['base_url'], 'Not a valid base path: "%s"');
        }
        $container->setParameter($this->getAlias() . '.proxy_client.varnish.servers', $config['servers']);
        $container->setParameter($this->getAlias() . '.proxy_client.varnish.base_url', $config['base_url']);
    }

    private function loadNginx(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('nginx.xml');
        foreach ($config['servers'] as $url) {
            $this->validateUrl($url, 'Not a valid nginx server address: "%s"');
        }
        if (!empty($config['base_url'])) {
            $this->validateUrl($config['base_url'], 'Not a valid base path: "%s"');
        }
        $container->setParameter($this->getAlias() . '.proxy_client.nginx.servers', $config['servers']);
        $container->setParameter($this->getAlias() . '.proxy_client.nginx.base_url', $config['base_url']);
        $container->setParameter($this->getAlias() . '.proxy_client.nginx.purge_location', $config['purge_location']);
    }

    private function loadCacheManager(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $container->setParameter($this->getAlias().'.cache_manager.route_invalidators', $config['route_invalidators']);

        $container->setParameter(
            $this->getAlias() . '.cache_manager.additional_status',
            isset($config['additional_status']) ? $config['additional_status'] : array()
        );
        $container->setParameter(
            $this->getAlias() . '.cache_manager.match_response',
            isset($config['match_response']) ? $config['match_response'] : null
        );
        $loader->load('cache_manager.xml');
        if (version_compare(Kernel::VERSION, '2.4.0', '>=')) {
            $container
                ->getDefinition('fos_http_cache.command.invalidate_path')
                ->addTag('console.command')
            ;
        }

        if ($config['tag_listener']['enabled']) {
            // true or auto
            if (class_exists('\Symfony\Component\ExpressionLanguage\ExpressionLanguage')) {
                $loader->load('tag_listener.xml');
            } elseif (true === $config['tag_listener']['enabled']) {
                // silently skip if set to auto
                throw new InvalidConfigurationException('The TagListener requires symfony/expression-language');
            }
        }
    }

    private function validateUrl($url, $msg)
    {
        if (false === strpos($url, '://')) {
            $url = sprintf('%s://%s', 'http', $url);
        }

        if (!$parts = parse_url($url)) {
            throw new InvalidConfigurationException(sprintf($msg, $url));
        }
    }
}
