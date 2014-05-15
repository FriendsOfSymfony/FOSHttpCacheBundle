<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\DependencyInjection;

use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class FOSHttpCacheExtensionTest
 */
class FOSHttpCacheExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FOSHttpCacheExtension
     *
     * Extension
     */
    protected $extension;

    /**
     * Setup method
     */
    protected function setUp()
    {
        $this->extension = new FOSHttpCacheExtension();
    }

    /**
     * test config load varnish
     */
    public function testConfigLoadVarnish()
    {
        $container = new ContainerBuilder();
        $this->extension->load(array($this->getBaseConfig()), $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
    }

    /**
     * Test configuration
     *
     * @dataProvider dataConfiguration
     */
    public function testConfiguration($config)
    {
        $container = new ContainerBuilder();
        $this->extension->load(array($config), $container);
    }

    /**
     * Provide data for dataConfiguration test case
     *
     * @return array Data configuration
     */
    public function dataConfiguration()
    {
        return array(

            /**
             * Empty config
             */
            array(
                array(),
            ),

            /**
             * config load invalidators
             */
            array(
                $this->getBaseConfig() + array(
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
                ),
            ),

            /**
             * config load rules
             */
                array(
                    array(
                        'rules' => array(
                            array(
                                'path' => '^/$',
                                'host' => 'fos.lo',
                                'methods' => array('GET', 'HEAD'),
                                'ips' => array('1.1.1.1', '2.2.2.2'),
                                'attributes' => array(
                                    '_controller' => '^AcmeBundle:Default:index$',
                                ),
                                'unless_role' => 'ROLE_NO_CACHE',
                                'controls' => array('etag' => '42'),
                                'reverse_proxy_ttl' => 42,
                                'vary' => array('Cookie', 'Accept-Language')
                            )
                        )
                    ),
                ),

            /**
             * config load rules split
             */
            array(
                array(
                    'rules' => array(
                        array(
                            'methods' => 'GET,HEAD',
                            'ips' => '1.1.1.1,2.2.2.2',
                            'attributes' => array(
                                '_controller' => '^AcmeBundle:Default:index$',
                            ),
                            'vary' => 'Cookie, Accept-Language',
                        )
                    )
                ),
            ),

            /**
             * config load rules defaults
             */
            array(
                array(
                    'rules' => array(
                        array(
                        )
                    )
                ),
            ),

            /**
             * config load authorization listener
             */
            array(
                array('authorization_listener' => true),
            ),

            /**
             * config load flash message listener
             */
            array(
                array(
                    'flash_message_listener' => array(
                        'name' => 'myflashes',
                        'path' => '/test',
                        'host' => '*.fos.lo',
                        'secure' => true,
                        'httpOnly' => false,
                    )
                ),
            ),

            /**
             * config load flash message listener defaults
             */
            array(
                array('flash_message_listener' => true),
            ),
        );
    }

    /**
     * Return base config
     *
     * @return array Base config
     */
    protected function getBaseConfig()
    {
        return array(
            'proxy_client' => array(
                'varnish' => array(
                    'base_url' => 'my_hostname',
                    'servers' => array(
                        '127.0.0.1'
                    )
                )
            )
        );
    }

}
