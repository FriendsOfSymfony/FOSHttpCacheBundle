<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\DependencyInjection;

use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use \Mockery;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

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

    public function testEmptyConfig()
    {
        $config = array();

        $container = new ContainerBuilder();
        $this->extension->load(array($config), $container);
    }

    public function testConfigLoadInvalidators()
    {
        $config = $this->getBaseConfig() + array(
            'cache_manager' => array(
                'route_invalidators' => array(
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
            'proxy_client' => array(
                'varnish' => array(
                    'servers' => array('1.2.3.4'),
                ),
            ),
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
                        ),
                        'headers' => array(
                            'cache_control' => array('etag' => '42'),
                            'reverse_proxy_ttl' => 42,
                            'vary' => array('Cookie', 'Accept-Language')
                        ),
                        'tags' => array('tag-a', 'tag-b')
                    )
                ),
                'proxy_client' => true,
            )
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);

        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition instanceof DefinitionDecorator &&
                $definition->getParent() === 'fos_http_cache.request_matcher'
            ) {
                if ($matcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $matcherId = $id;
                $matcherDefinition = $definition;
            }
        }

        // definition should exist
        $this->assertNotNull($matcherDefinition);

        // 5th argument should contain the controller name value
        $this->assertEquals(array('_controller' => '^AcmeBundle:Default:index$'), $matcherDefinition->getArgument(4));

        $ruleDefinition = null;
        foreach ($container->getDefinitions() as $definition) {
            if ($definition instanceof DefinitionDecorator &&
                $definition->getParent() === 'fos_http_cache.rule_matcher'
            ) {
                if ($ruleDefinition) {
                    $this->fail('More then one rule matcher was created');
                }
                $ruleDefinition = $definition;
            }
        }

        // definition should exist
        $this->assertNotNull($ruleDefinition);

        // first argument should be the reference to the matcher
        $reference = $ruleDefinition->getArgument(0);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $reference);
        $this->assertEquals($matcherId, (string) $reference);
    }

    /**
     * Check if comma separated strings are parsed as expected.
     */
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

        // Extract the corresponding definition
        $matcherDefinition = null;
        foreach ($container->getDefinitions() as $definition) {
            if ($definition instanceof DefinitionDecorator &&
                $definition->getParent() === 'fos_http_cache.request_matcher'
            ) {
                if ($matcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $matcherDefinition = $definition;
            }
        }

        // definition should exist
        $this->assertNotNull($matcherDefinition);

        $this->assertEquals(array('GET', 'HEAD'), $matcherDefinition->getArgument(2));
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

    public function testConfigUserContext()
    {
        $config = array(
            array('user_context' => array(
                'match'   => array(
                    'matcher_service' => 'my_request_matcher_id',
                    'method' => 'AUTHENTICATE',
                    'accept' => 'application/vnd.test'
                ),
                'user_identifier_headers' => array('X-Foo'),
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'role_provider' => true
            )),
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);

        $this->assertTrue($container->has('fos_http_cache.event_listener.user_context'));
        $this->assertTrue($container->has('fos_http_cache.user_context.hash_generator'));
        $this->assertTrue($container->has('fos_http_cache.user_context.request_matcher'));
        $this->assertTrue($container->has('fos_http_cache.user_context.role_provider'));

        $this->assertEquals(array('fos_http_cache.user_context.role_provider' => array(array())), $container->findTaggedServiceIds('fos_http_cache.user_context_provider'));
    }

    public function testConfigWithoutUserContext()
    {
        $config = array(
            array('user_context' => array(
                'enabled' => false,
                'match'   => array(
                    'matcher_service' => 'my_request_matcher_id',
                    'method' => 'AUTHENTICATE',
                    'accept' => 'application/vnd.test'
                ),
                'user_identifier_headers' => array('X-Foo'),
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'role_provider' => true
            )),
        );

        $container = new ContainerBuilder();
        $this->extension->load($config, $container);

        $this->assertFalse($container->has('fos_http_cache.event_listener.user_context'));
        $this->assertFalse($container->has('fos_http_cache.user_context.hash_generator'));
        $this->assertFalse($container->has('fos_http_cache.user_context.request_matcher'));
        $this->assertFalse($container->has('fos_http_cache.user_context.role_provider'));
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
