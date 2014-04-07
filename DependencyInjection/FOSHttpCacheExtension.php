<?php

namespace FOS\HttpCacheBundle\DependencyInjection;

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
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('cache_manager.xml');

        $container->setParameter($this->getAlias().'.debug', $config['debug']);
        $container->setParameter($this->getAlias().'.invalidators', $config['invalidators']);

        if (($config['debug']) || (!empty($config['rules']))) {
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

        if (isset($config['varnish'])) {
            $this->loadVarnish($container, $loader, $config);
        }

        if ($config['authorization_listener']) {
            $loader->load('authorization_request_listener.xml');
        }

        if ($config['tag_listener']['enabled']) {
            if (!class_exists('\Symfony\Component\ExpressionLanguage\ExpressionLanguage')) {
                throw new \RuntimeException('The TagListener requires symfony/expression-language');
            }

            $loader->load('tag_listener.xml');
        }

        if (!empty($config['flash_message_listener']) && $config['flash_message_listener']['enabled']) {
            $loader->load('flash_message_listener.xml');

            $container->setParameter($this->getAlias().'.event_listener.flash_message.options', $config['flash_message_listener']);
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
    protected function loadVarnish(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('varnish.xml');
        foreach ($config['varnish']['servers'] as $url) {
            $this->validateUrl($url, 'Not a valid varnish server address: "%s"');
        }
        if (!empty($config['varnish']['base_url'])) {
            $this->validateUrl($config['varnish']['base_url'], 'Not a valid base path: "%s"');
        }
        $container->setParameter($this->getAlias() . '.varnish.servers', $config['varnish']['servers']);
        $container->setParameter($this->getAlias() . '.varnish.base_url', $config['varnish']['base_url']);
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
