<?php

namespace Liip\CacheControlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor,
    Symfony\Component\Config\FileLocator,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Reference,
    Symfony\Component\DependencyInjection\DefinitionDecorator,
    Symfony\Component\DependencyInjection\Exception\RuntimeException;

class LiipCacheControlExtension extends Extension
{
    /**
     * Loads the services based on your application configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader =  new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (!empty($config['rules'])) {
            $loader->load('rule_response_listener.xml');
            foreach ($config['rules'] as $cache) {
                $matcher = $this->createRequestMatcher(
                    $container,
                    $cache['path'],
                    $cache['domain']
                );

                unset(
                    $cache['path'],
                    $cache['domain']
                );

                $container->getDefinition($this->getAlias().'.response_listener')
                          ->addMethodCall('add', array($matcher, $cache));
            }
        }

        if (!empty($config['varnish'])) {

            if (!extension_loaded('curl')) {
                throw new RuntimeException('Varnish Helper requires cUrl php extension. Please install it to continue');

            }

            $loader->load('varnish_helper.xml');
            $container->setParameter($this->getAlias().'.varnish.ips', $config['varnish']['ips']);
            $container->setParameter($this->getAlias().'.varnish.domain', $config['varnish']['domain']);
            $container->setParameter($this->getAlias().'.varnish.port', $config['varnish']['port']);
        }

        if ($config['authorization_listener']) {
            $loader->load('authorization_request_listener.xml');
        }

        if (!empty($config['flash_message_listener']) && $config['flash_message_listener']['enabled']) {
            $loader->load('flash_message_listener.xml');

            $container->setParameter($this->getAlias().'.flash_message_listener.options', $config['flash_message_listener']);
        }
    }

    protected function createRequestMatcher(ContainerBuilder $container, $path = null, $domain = null)
    {
        $serialized = serialize(array($path));
        $id = $this->getAlias().'.request_matcher.'.md5($serialized).sha1($serialized);

        if (!$container->hasDefinition($id)) {
            // only add arguments that are necessary
            $arguments = array($path, $domain);

            $container
                ->setDefinition($id, new DefinitionDecorator($this->getAlias().'.request_matcher'))
                ->setArguments($arguments)
            ;
        }

        return new Reference($id);
    }
}
