<?php

namespace FOS\HttpCacheBundle\Tests\Unit\DependencyInjection;

use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use \Mockery;

class FOSHttpCacheExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FOSHttpCacheExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new FOSHttpCacheExtension();
    }

    public function testConfigLoadVarnish()
    {
        $container = new ContainerBuilder();
        $this->extension->load(array($this->getBaseConfig()), $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
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

    public function testConfigLoadRules()
    {
        $config = array(
            array(
                'rules' => array(
                    array(
                        'match' => array(
                            'path' => '^/$',
                            'host' => 'fos.lo',
                            'methods' => array('GET', 'HEAD'),
                            'ips' => array('1.1.1.1', '2.2.2.2'),
                            'attributes' => array(
                                '_controller' => '^AcmeBundle:Default:index$',
                            ),
                            'unless_role' => 'ROLE_NO_CACHE',
                        ),
                        'headers' => array(
                            'cache_control' => array('etag' => '42'),
                            'reverse_proxy_ttl' => 42,
                            'vary' => array('Cookie', 'Accept-Language')
                        )
                    )
                )
            )
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

    public function testConfigLoadRulesSplit()
    {
        $config = array(
            array('rules' => array(
                array(
                    'match' => array(
                        'methods' => 'GET,HEAD',
                        'ips' => '1.1.1.1,2.2.2.2',
                        'attributes' => array(
                            '_controller' => '^AcmeBundle:Default:index$',
                        ),
                    ),
                    'headers' => array(
                        'vary' => 'Cookie, Accept-Language',
                    )
                )
            ))
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidDate()
    {
        $config = array(
            array(
                'rules' => array(
                    array(
                        'match' => array(),
                        'headers' => array(
                            'last_modified' => 'no valid date',
                        )
                    )
                )
            )
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

    public function testConfigLoadRulesDefaults()
    {
        $config = array();

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

    public function testConfigLoadAuthorizationListener()
    {
        $config = array(
            array('authorization_listener' => true,
            ),
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

    public function testConfigLoadFlashMessageListener()
    {
        $config = array(
            array('flash_message_listener' => array(
                'name' => 'myflashes',
                'path' => '/test',
                'host' => '*.fos.lo',
                'secure' => true,
                'httpOnly' => false,
            )),
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

    public function testConfigLoadFlashMessageListenerDefaults()
    {
        $config = array(
            array('flash_message_listener' => true,
            ),
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);
    }

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
