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
        $loader->load('services.xml');

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
                    $cache['method'],
                    $cache['ips'],
                    $cache['attributes']
                );

                unset(
                    $cache['path'],
                    $cache['host'],
                    $cache['method'],
                    $cache['ips'],
                    $cache['attributes']
                );
                if (isset($cache['controls'])) {
                    foreach ($cache['controls'] as $key => $value) {
                        unset($cache['controls'][$key]);
                        $cache['controls'][str_replace('_', '-', $key)] = $value;
                    }
                }

                $container->getDefinition($this->getAlias().'.response_listener')
                          ->addMethodCall('add', array($matcher, $cache));
            }
        }

        if (isset($config['varnish'])) {
            if (!class_exists('\Guzzle\Http\Client')) {
                throw new \RuntimeException('The Varnish component requires guzzle/http to be installed');
            }
            $loader->load('varnish.xml');
            $container->setParameter($this->getAlias().'.varnish.ips', $config['varnish']['ips']);
            $container->setParameter($this->getAlias().'.varnish.host', $config['varnish']['host']);
        }

        if ($config['authorization_listener']) {
            $loader->load('authorization_request_listener.xml');
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
