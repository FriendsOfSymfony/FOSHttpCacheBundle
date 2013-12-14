<?php

namespace Driebit\HttpCacheBundle\Tests\DependencyInjection;

use Driebit\HttpCacheBundle\DependencyInjection\DriebitHttpCacheExtension;
use \Mockery;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DriebitHttpCacheExtentionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DriebitHttpCacheExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new DriebitHttpCacheExtension();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "http_cache" at path "driebit_http_cache" must be configured.
     */
    public function testConfigLoadDefault()
    {
        $extension = new DriebitHttpCacheExtension();
        $container = new ContainerBuilder();
        $extension->load(array(array()), $container);

        $this->assertTrue($container->hasDefinition('driebit_http_cache.cache_manager'));
    }

    public function testConfigLoadVanish()
    {
        $container = new ContainerBuilder();
        $this->extension->load(array($this->getBaseConfig()), $container);

        $this->assertTrue($container->hasDefinition('driebit_http_cache.varnish'));
        $this->assertTrue($container->hasAlias('driebit_http_cache.http_cache'));
        $this->assertTrue($container->hasDefinition('driebit_http_cache.event_listener.invalidation'));
    }

    public function testConfigLoadInvalidators()
    {
        $config = $this->getBaseConfig() + array(
            'invalidators' => array(
                array(
                    'name' => 'invalidator1',
                    'origin_routes' => array(
                        'my_route'
                    ),
                    'invalidate_routes' => array(
                        array(
                            'name' => 'invalidate_route1',
                        )
                    )
                )
            )
        );

        $container = new ContainerBuilder();
        $this->extension->load(array($config), $container);
    }

    protected function getBaseConfig()
    {
        return array(
            'http_cache' => array(
                'varnish' => array(
                    'host' => 'my_hostname',
                    'ips' => array(
                        '127.0.0.1'
                    )
                )
            )
        );
    }

} 
