<?php

namespace Driebit\HttpCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DriebitHttpCacheExtension extends Extension
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
        $container->setParameter('driebit_http_cache.invalidators', $config['invalidators']);

        if (isset($config['http_cache']['varnish'])) {
            $loader->load('varnish.xml');
            $container->setParameter('driebit_http_cache.http_cache.varnish.ips', $config['http_cache']['varnish']['ips']);
            $container->setParameter('driebit_http_cache.http_cache.varnish.host', $config['http_cache']['varnish']['host']);

        }
    }
}
