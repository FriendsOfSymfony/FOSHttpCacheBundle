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
use Symfony\Component\DependencyInjection\Reference;

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
        $this->extension->load([$this->getBaseConfig()], $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.tag'));
    }

    public function testConfigLoadVarnishCustomClient()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['http']['http_client'] = 'my_guzzle';
        $this->extension->load([$config], $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.varnish.http_dispatcher'));
        $def = $container->getDefinition('fos_http_cache.proxy_client.varnish.http_dispatcher');
        $this->assertInstanceOf(Reference::class, $def->getArgument(2));
        $this->assertEquals('my_guzzle', $def->getArgument(2)->__toString());
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testConfigLoadVarnishInvalidUrl()
    {
        $container = $this->createContainer();
        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['http']['base_url'] = 'ftp:not a valid url';

        $this->extension->load([$config], $container);
    }

    public function testConfigLoadNginx()
    {
        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'nginx' => [
                        'http' => [
                            'base_url' => 'my_hostname',
                            'servers' => [
                                '127.0.0.1',
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.http.symfony_response_tagger'));
    }

    public function testConfigLoadSymfony()
    {
        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'symfony' => [
                        'http' => [
                            'base_url' => 'my_hostname',
                            'servers' => [
                                '127.0.0.1',
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.symfony'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.http.symfony_response_tagger'));
    }

    public function testConfigCustomClient()
    {
        $container = $this->createContainer();
        $this->extension->load([
            [
                'cache_manager' => [
                    'custom_proxy_client' => 'app.cache.client',
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.symfony'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.http.symfony_response_tagger'));
    }

    public function testEmptyConfig()
    {
        $config = [];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertFalse($container->has('fos_http_cache.user_context.logout_handler'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage You can not enable cache tagging with the nginx client
     */
    public function testConfigTagNotSupported()
    {
        $config = [
                'proxy_client' => [
                    'nginx' => [
                        'http' => [
                            'base_url' => 'my_hostname',
                            'servers' => [
                                '127.0.0.1',
                            ],
                        ],
                    ],
                ],
                'tags' => [
                    'enabled' => true,
                ],
            ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);
    }

    public function testConfigLoadTagRules()
    {
        $config = $this->getBaseConfig() + [
            'tags' => [
                'rules' => [
                    [
                        'match' => [
                            'path' => '^/$',
                            'host' => 'fos.lo',
                            'methods' => ['GET', 'HEAD'],
                            'ips' => ['1.1.1.1', '2.2.2.2'],
                            'attributes' => [
                                '_controller' => '^AcmeBundle:Default:index$',
                            ],
                        ],
                        'tags' => ['tag-a', 'tag-b'],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertMatcherCreated($container, ['_controller' => '^AcmeBundle:Default:index$']);
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.tag');
    }

    public function testConfigLoadInvalidatorRules()
    {
        $config = $this->getBaseConfig() + [
            'invalidation' => [
                'rules' => [
                    [
                        'match' => [
                            'attributes' => [
                                '_route' => 'my_route',
                            ],
                        ],
                        'routes' => [
                            'invalidate_route1' => [
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertMatcherCreated($container, ['_route' => 'my_route']);
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.invalidation');

        // Test for runtime errors
        $container->compile();
    }

    public function testConfigLoadCacheControl()
    {
        $config = [
            'cache_control' => [
                'rules' => [
                    [
                        'match' => [
                            'path' => '^/$',
                            'host' => 'fos.lo',
                            'methods' => ['GET', 'HEAD'],
                            'ips' => ['1.1.1.1', '2.2.2.2'],
                            'attributes' => [
                                '_controller' => '^AcmeBundle:Default:index$',
                            ],
                        ],
                        'headers' => [
                            'cache_control' => ['public' => true],
                            'reverse_proxy_ttl' => 42,
                            'vary' => ['Cookie', 'Accept-Language'],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertMatcherCreated($container, ['_controller' => '^AcmeBundle:Default:index$']);
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.cache_control');
    }

    /**
     * Check if comma separated strings are parsed as expected.
     */
    public function testConfigLoadCacheControlSplit()
    {
        $config = [
            'cache_control' => [
                'rules' => [
                    [
                        'match' => [
                            'methods' => 'GET,HEAD',
                            'ips' => '1.1.1.1,2.2.2.2',
                            'attributes' => [
                                '_controller' => '^AcmeBundle:Default:index$',
                            ],
                        ],
                        'headers' => [
                            'vary' => 'Cookie, Accept-Language',
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $matcherDefinition = $this->assertMatcherCreated($container, ['_controller' => '^AcmeBundle:Default:index$']);
        $this->assertEquals(['GET', 'HEAD'], $matcherDefinition->getArgument(2));
    }

    public function testConfigUserContext()
    {
        $config = $this->getBaseConfig() + [
            'user_context' => [
                'match' => [
                    'matcher_service' => 'my_request_matcher_id',
                    'method' => 'AUTHENTICATE',
                    'accept' => 'application/vnd.test',
                ],
                'user_identifier_headers' => ['X-Foo'],
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'always_vary_on_context_hash' => true,
                'role_provider' => true,
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertTrue($container->has('fos_http_cache.event_listener.user_context'));
        $this->assertTrue($container->has('fos_http_cache.user_context.hash_generator'));
        $this->assertTrue($container->has('fos_http_cache.user_context.request_matcher'));
        $this->assertTrue($container->has('fos_http_cache.user_context.role_provider'));
        $this->assertTrue($container->has('fos_http_cache.user_context.logout_handler'));

        $this->assertEquals(['fos_http_cache.user_context.role_provider' => [[]]], $container->findTaggedServiceIds('fos_http_cache.user_context_provider'));
    }

    public function testConfigWithoutUserContext()
    {
        $config = [[
            'user_context' => [
                'enabled' => false,
                'match' => [
                    'matcher_service' => 'my_request_matcher_id',
                    'method' => 'AUTHENTICATE',
                    'accept' => 'application/vnd.test',
                ],
                'user_identifier_headers' => ['X-Foo'],
                'user_hash_header' => 'X-Bar',
                'hash_cache_ttl' => 30,
                'always_vary_on_context_hash' => true,
                'role_provider' => true,
            ],
        ]];

        $container = $this->createContainer();
        $this->extension->load($config, $container);

        $this->assertFalse($container->has('fos_http_cache.event_listener.user_context'));
        $this->assertFalse($container->has('fos_http_cache.user_context.hash_generator'));
        $this->assertFalse($container->has('fos_http_cache.user_context.request_matcher'));
        $this->assertFalse($container->has('fos_http_cache.user_context.role_provider'));
        $this->assertFalse($container->has('fos_http_cache.user_context.logout_handler'));
    }

    public function testConfigLoadFlashMessageListener()
    {
        $config = [
            ['flash_message' => true,
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load($config, $container);
    }

    protected function createContainer()
    {
        $container = new ContainerBuilder(
            new ParameterBag(['kernel.debug' => false])
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
        return [
            'proxy_client' => [
                'varnish' => [
                    'http' => [
                        'base_url' => 'my_hostname',
                        'servers' => [
                            '127.0.0.1',
                        ],
                    ],
                ],
            ],
        ];
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

        // 5th argument should contain the request attribute criteria
        $this->assertEquals($attributes, $matcherDefinition->getArgument(4));

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
