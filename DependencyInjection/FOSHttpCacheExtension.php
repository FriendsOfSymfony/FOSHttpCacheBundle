<?php

namespace FOS\HttpCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

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

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('cache_manager.xml');

        $container->setParameter($this->getAlias().'.debug', $config['debug']);
        $container->setParameter($this->getAlias().'.invalidators', $config['invalidators']);

        if (!empty($config['rules'])) {
            $loader->load('rule_response_listener.xml');
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

                $container->getDefinition($this->getAlias().'.response_listener')
                          ->addMethodCall('add', array($matcher, $cache));
            }
        }

        if (isset($config['varnish'])) {
            $loader->load('varnish.xml');
            $container->setParameter($this->getAlias().'.varnish.ips', $config['varnish']['ips']);
            $container->setParameter($this->getAlias().'.varnish.host', $config['varnish']['host']);
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

            $container->setParameter($this->getAlias().'.flash_message_listener.options', $config['flash_message_listener']);
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
}
