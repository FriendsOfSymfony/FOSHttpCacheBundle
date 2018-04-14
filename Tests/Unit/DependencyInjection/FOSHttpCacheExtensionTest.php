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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\Kernel;

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
        $container = $this->createContainer();
        $this->extension->load(array($this->getBaseConfig()), $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.handler.tag_handler'));

        $this->assertFalse($container->hasParameter('fos_http_cache.proxy_client.varnish.guzzle_client'));
    }

    public function testConfigLoadVarnishCustomGuzzle()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['guzzle_client'] = 'my_guzzle';
        $this->extension->load(array($config), $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $def = $container->getDefinition('fos_http_cache.proxy_client.varnish');
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $def->getArgument(2));
        $this->assertEquals('fos_http_cache.proxy_client.varnish.guzzle_client', $def->getArgument(2)->__toString());

        $this->assertTrue($container->hasAlias('fos_http_cache.proxy_client.varnish.guzzle_client'));
        $this->assertEquals('my_guzzle', $container->getAlias('fos_http_cache.proxy_client.varnish.guzzle_client'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testConfigLoadVarnishInvalidUrl()
    {
        $container = $this->createContainer();
        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['base_url'] = 'ftp:not a valid url';

        $this->extension->load(array($config), $container);
    }

    public function testConfigLoadNginx()
    {
        $container = $this->createContainer();
        $this->extension->load(array(
            array(
                'proxy_client' => array(
                    'nginx' => array(
                        'base_url' => 'my_hostname',
                        'servers' => array(
                            '127.0.0.1',
                        ),
                    ),
                ),
            ),
        ), $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.handler.tag_handler'));
    }

    public function testConfigLoadSymfony()
    {
        $container = $this->createContainer();
        $this->extension->load(array(
            array(
                'proxy_client' => array(
                    'symfony' => array(
                        'base_url' => 'my_hostname',
                        'servers' => array(
                            '127.0.0.1',
                        ),
                    ),
                ),
            ),
        ), $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.symfony'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.handler.tag_handler'));
    }

    public function testConfigCustomClient()
    {
        $container = $this->createContainer();
        $this->extension->load(array(
            array(
                'cache_manager' => array(
                    'custom_proxy_client' => 'app.cache.client',
                ),
            ),
        ), $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.symfony'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.handler.tag_handler'));
    }

    public function testEmptyConfig()
    {
        $config = array();

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);

        $this->assertFalse($container->has('fos_http_cache.user_context.logout_handler'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage You can not enable cache tagging with nginx
     */
    public function testConfigTagNotSupported()
    {
        $config = array(
                'proxy_client' => array(
                    'nginx' => array(
                        'base_url' => 'my_hostname',
                        'servers' => array(
                            '127.0.0.1',
                        ),
                    ),
                ),
                'tags' => array(
                    'enabled' => true,
                ),
            );

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);
    }

    public function testConfigLoadTagRules()
    {
        $config = $this->getBaseConfig() + array(
            'tags' => array(
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
                        'tags' => array('tag-a', 'tag-b'),
                    ),
                ),
            ),
        );

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);

        $this->assertMatcherCreated($container, array('_controller' => '^AcmeBundle:Default:index$'));
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.tag');
    }

    public function testConfigLoadInvalidatorRules()
    {
        $config = $this->getBaseConfig() + array(
            'invalidation' => array(
                'rules' => array(
                    array(
                        'match' => array(
                            'attributes' => array(
                                '_route' => 'my_route',
                            ),
                        ),
                        'routes' => array(
                            'invalidate_route1' => array(
                            ),
                        ),
                    ),
                ),
            ),
        );

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);

        $this->assertMatcherCreated($container, array('_route' => 'my_route'));
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.invalidation');

        // Test for runtime errors
        $container->compile();
    }

    public function testConfigLoadCacheControl()
    {
        $config = array(
            'cache_control' => array(
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
                            'cache_control' => array('public' => true),
                            'reverse_proxy_ttl' => 42,
                            'vary' => array('Cookie', 'Accept-Language'),
                        ),
                    ),
                ),
            ),
        );

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);

        $this->assertMatcherCreated($container, array('_controller' => '^AcmeBundle:Default:index$'));
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.cache_control');
    }

    /**
     * Check if comma separated strings are parsed as expected.
     */
    public function testConfigLoadCacheControlSplit()
    {
        $config = array(
            'cache_control' => array(
                'rules' => array(
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
                        ),
                    ),
                ),
            ),
        );

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);

        $matcherDefinition = $this->assertMatcherCreated($container, array('_controller' => '^AcmeBundle:Default:index$'));
        $this->assertEquals(array('GET', 'HEAD'), $matcherDefinition->getArgument(2));
    }

    public function testConfigUserContext()
    {
        $config = $this->getBaseConfig() + array(
            'user_context' => array(
                'match' => array(
                    'matcher_service' => 'my_request_matcher_id',
                    'method' => 'AUTHENTICATE',
                    'accept' => 'application/vnd.test',
                ),
                'user_identifier_headers' => array('X-Foo'),
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'role_provider' => true,
            ),
        );

        $container = $this->createContainer();
        $this->extension->load(array($config), $container);

        $this->assertTrue($container->has('fos_http_cache.event_listener.user_context'));
        $this->assertTrue($container->has('fos_http_cache.user_context.hash_generator'));
        $this->assertTrue($container->has('fos_http_cache.user_context.request_matcher'));
        $this->assertTrue($container->has('fos_http_cache.user_context.role_provider'));
        $this->assertTrue($container->has('fos_http_cache.user_context.logout_handler'));

        $this->assertEquals(array('fos_http_cache.user_context.role_provider' => array(array())), $container->findTaggedServiceIds('fos_http_cache.user_context_provider'));
    }

    public function testConfigWithoutUserContext()
    {
        $config = array(
            array('user_context' => array(
                'enabled' => false,
                'match' => array(
                    'matcher_service' => 'my_request_matcher_id',
                    'method' => 'AUTHENTICATE',
                    'accept' => 'application/vnd.test',
                ),
                'user_identifier_headers' => array('X-Foo'),
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'role_provider' => true,
            )),
        );

        $container = $this->createContainer();
        $this->extension->load($config, $container);

        $this->assertFalse($container->has('fos_http_cache.event_listener.user_context'));
        $this->assertFalse($container->has('fos_http_cache.user_context.hash_generator'));
        $this->assertFalse($container->has('fos_http_cache.user_context.request_matcher'));
        $this->assertFalse($container->has('fos_http_cache.user_context.role_provider'));
        $this->assertFalse($container->has('fos_http_cache.user_context.logout_handler'));
        $this->assertFalse($container->has('fos_http_cache.user_context.session_listener'));
    }

    /**
     * @group sf34
     */
    public function testSessionListenerIsDecoratedIfNeeded()
    {
        $config = array(
            array('user_context' => array(
                'user_identifier_headers' => array('X-Foo'),
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'role_provider' => true,
            )),
        );

        $container = $this->createContainer();
        $this->extension->load($config, $container);

        // The whole definition should be removed for Symfony < 3.4
        if (version_compare(Kernel::VERSION, '3.4', '<')) {
            $this->assertFalse($container->hasDefinition('fos_http_cache.user_context.session_listener'));
        } else {
            $this->assertTrue($container->hasDefinition('fos_http_cache.user_context.session_listener'));

            $definition = $container->getDefinition('fos_http_cache.user_context.session_listener');

            $this->assertSame('x-bar', $definition->getArgument(1));
            $this->assertSame(array('x-foo'), $definition->getArgument(2));
        }
    }

    public function testConfigLoadFlashMessageSubscriber()
    {
        $config = array(
            array('flash_message' => true,
            ),
        );

        $container = $this->createContainer();
        $this->extension->load($config, $container);
    }

    protected function createContainer()
    {
        $container = new ContainerBuilder(
            new ParameterBag(array('kernel.debug' => false))
        );

        // The cache_manager service depends on the router service
        $container->setDefinition(
            'router',
            new Definition('\Symfony\Component\Routing\Router')
        );

        return $container;
    }

    protected function getBaseConfig()
    {
        return array(
            'proxy_client' => array(
                'varnish' => array(
                    'base_url' => 'my_hostname',
                    'servers' => array(
                        '127.0.0.1',
                    ),
                ),
            ),
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $attributes
     *
     * @return DefinitionDecorator
     */
    private function assertMatcherCreated(ContainerBuilder $container, array $attributes)
    {
        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition instanceof DefinitionDecorator &&
                'fos_http_cache.request_matcher' === $definition->getParent()
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

        // 5th argument should contain the request attribute criteria
        $this->assertEquals($attributes, $matcherDefinition->getArgument(4));

        $ruleDefinition = null;
        foreach ($container->getDefinitions() as $definition) {
            if ($definition instanceof DefinitionDecorator &&
                'fos_http_cache.rule_matcher' === $definition->getParent()
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

        return $matcherDefinition;
    }

    /**
     * Assert that the service $id exists and has a method call mapped onto it.
     *
     * @param ContainerBuilder $container
     * @param string           $id        The service id to investigate
     */
    private function assertListenerHasRule(ContainerBuilder $container, $id)
    {
        $this->assertTrue($container->hasDefinition($id));
        $listener = $container->getDefinition($id);
        $this->assertTrue($listener->hasMethodCall('addRule'));
        $this->assertCount(1, $listener->getMethodCalls());
    }
}
