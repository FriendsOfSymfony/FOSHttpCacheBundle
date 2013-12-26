<?php

namespace FOS\HttpCacheBundle\Tests\DependencyInjection;

use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use \Mockery;

class FOSHttpCacheExtentionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DriebitHttpCacheExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new FOSHttpCacheExtension();
    }

    public function testConfigLoadVanish()
    {
        $container = new ContainerBuilder();
        $this->extension->load(array($this->getBaseConfig()), $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.varnish'));
        $this->assertTrue($container->hasAlias('fos_http_cache.http_cache'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
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
            'varnish' => array(
                'host' => 'my_hostname',
                'ips' => array(
                    '127.0.0.1'
                )
            )
        );
    }

} 
