<?php

namespace Driebit\HttpCacheBundle\Tests\DependencyInjection;

use Driebit\HttpCacheBundle\DependencyInjection\DriebitHttpCacheExtension;
use \Mockery;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DriebitHttpCacheExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigLoadDefault()
    {
        $extension = new DriebitHttpCacheExtension();
        $container = new ContainerBuilder();
        $extension->load(array(array()), $container);

        $this->assertTrue($container->hasDefinition('driebit_http_cache.cache_manager'));
    }

    public function testConfigLoadVanish()
    {
        $config = array(
            'http_cache' => array(
                'varnish' => array(
                    'host' => 'my_hostname'
                )
            )
        );

        $extension = new DriebitHttpCacheExtension();
        $container = new ContainerBuilder();
        $extension->load(array($config), $container);

        $this->assertTrue($container->hasDefinition('driebit_http_cache.varnish'));
        $this->assertTrue($container->hasAlias('driebit_http_cache.http_cache'));
    }

    public function testConfigLoadInvalidators()
    {
        $config = array(
            'http_cache' => array(
                'varnish' => array(
                    'host' => 'my_hostname'
                )
            ),
            'invalidators' => array(
                array(
                    'name' => 'ding'
                )
            )
        );

        $extension = new DriebitHttpCacheExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $extension->load(array($config), $container);

        $this->assertTrue($container->hasDefinition('driebit_http_cache.event_listener.invalidation'));
    }
}