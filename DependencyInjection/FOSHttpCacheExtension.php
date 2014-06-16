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

        if (!empty($config['rules'])) {
            foreach ($config['rules'] as $cache) {
                $cache['ips'] = (empty($cache['ips'])) ? null : $cache['ips'];

                $matcher = $this->createRequestMatcher(
                    $container,
                    $cache['path'],
                    $cache['host'],
                    $cache['methods'],
                    $cache['ips'],
                    $cache['attributes']
                );

                unset(
                    $cache['path'],
                    $cache['host'],
                    $cache['methods'],
                    $cache['ips'],
                    $cache['attributes']
                );

                $container
                    ->getDefinition($this->getAlias().'.event_listener.cache_control')
                    ->addMethodCall('add', array($matcher, $cache))
                ;
            }
        }

        if (isset($config['proxy_client'])) {
            $container->setParameter($this->getAlias().'.invalidators', $config['invalidators']);
            $this->loadProxyClient($container, $loader, $config['proxy_client']);

            $loader->load('cache_manager.xml');

            if ($config['tag_listener']['enabled']) {
                // true or auto
                if (class_exists('\Symfony\Component\ExpressionLanguage\ExpressionLanguage')) {
                    $loader->load('tag_listener.xml');
                } elseif (true === $config['tag_listener']['enabled']) {
                    // silently skip if set to auto
                    throw new \RuntimeException('The TagListener requires symfony/expression-language');
                }
            }

            if (version_compare(Kernel::VERSION, '2.4.0', '>=')) {
                $container
                    ->getDefinition('fos_http_cache.command.invalidate_path')
                    ->addTag('console.command')
                ;
            }
        } elseif (!empty($config['invalidators'])) {
            throw new InvalidConfigurationException('You need to configure a proxy client to use the invalidators.');
        } elseif (true === $config['tag_listener']['enabled']) {
            throw new InvalidConfigurationException('You need to configure a proxy client to use the tag listener.');
        }

        if ($config['authorization_listener']) {
            $loader->load('authorization_request_listener.xml');
        }

        if (!empty($config['flash_message_listener']) && $config['flash_message_listener']['enabled']) {
            $container->setParameter($this->getAlias().'.event_listener.flash_message.options', $config['flash_message_listener']);

            $loader->load('flash_message_listener.xml');
        }
    }

    protected function createRequestMatcher(ContainerBuilder $container, $path = null, $host = null, $methods = null, $ips = null, array $attributes = array())
    {
        $arguments = array($path, $host, $methods, $ips, $attributes);
        $serialized = serialize($arguments);
        $id = $this->getAlias().'.request_matcher.'.md5($serialized).sha1($serialized);

        if (!$container->hasDefinition($id)) {
            // only add arguments that are necessary
            $container
                ->setDefinition($id, new DefinitionDecorator($this->getAlias().'.request_matcher'))
                ->setArguments($arguments)
            ;
        }

        return new Reference($id);
    }

    /**
     * @param ContainerBuilder $container
     * @param $loader
     * @param $config
     */
    protected function loadProxyClient(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $default = empty($config['default']) ? false : $config['default'];
        if (isset($config['varnish'])) {
            $this->loadVarnish($container, $loader, $config['varnish']);
            if (!$default) {
                $default = 'varnish';
            }
        }

        $container->setAlias($this->getAlias() . '.default_proxy_client', $this->getAlias() . '.proxy_client.' . $default);
    }

    /**
     * @param ContainerBuilder $container
     * @param $loader
     * @param $config
     */
    protected function loadVarnish(ContainerBuilder $container, XmlFileLoader $loader, array $config)
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
