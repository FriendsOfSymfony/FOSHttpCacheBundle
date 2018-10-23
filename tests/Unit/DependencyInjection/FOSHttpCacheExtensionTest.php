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

use FOS\HttpCache\SymfonyCache\KernelDispatcher;
use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Router;

class FOSHttpCacheExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
        $this->assertTrue($container->hasDefinition('fos_http_cache.http.symfony_response_tagger'));
    }

    public function testConfigLoadSymfonyWithKernelDispatcher()
    {
        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'symfony' => [
                        'use_kernel_dispatcher' => true,
                    ],
                ],
            ],
        ], $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.symfony.http_dispatcher'));
        $this->assertSame(KernelDispatcher::class, $container->getDefinition('fos_http_cache.proxy_client.symfony.http_dispatcher')->getClass());
    }

    public function testConfigLoadNoop()
    {
        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'noop' => true,
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.nginx'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.noop'));
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

        $this->assertRequestMatcherCreated($container, ['_controller' => '^AcmeBundle:Default:index$']);
        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.tag');
        $this->assertFalse($container->hasDefinition('fos_http_cache.tag_handler.max_header_value_length_header_formatter'));
        $this->assertTrue($container->hasParameter('fos_http_cache.compiler_pass.tag_annotations'));
        $this->assertTrue($container->getParameter('fos_http_cache.compiler_pass.tag_annotations'));
    }

    public function testConfigLoadTagDisableAnnotations()
    {
        $config = $this->getBaseConfig() + [
            'tags' => [
                'annotations' => false,
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertTrue($container->hasParameter('fos_http_cache.compiler_pass.tag_annotations'));
        $this->assertFalse($container->getParameter('fos_http_cache.compiler_pass.tag_annotations'));
    }

    public function testConfigWithMaxHeaderLengthValueDecoratesTagService()
    {
        $config = $this->getBaseConfig() + [
            'tags' => [
                'max_header_value_length' => 2048,
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load([$config], $container);

        $this->assertTrue($container->hasDefinition('fos_http_cache.tag_handler.max_header_value_length_header_formatter'));
        $definition = $container->getDefinition('fos_http_cache.tag_handler.max_header_value_length_header_formatter');

        $this->assertSame('fos_http_cache.tag_handler.header_formatter', $definition->getDecoratedService()[0]);
        $this->assertSame('fos_http_cache.tag_handler.max_header_value_length_header_formatter.inner', (string) $definition->getArgument(0));
        $this->assertSame(2048, $definition->getArgument(1));
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

        $this->assertRequestMatcherCreated($container, ['_route' => 'my_route']);
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

        $requestMatcherId = $this->assertRequestMatcherCreated($container, ['_controller' => '^AcmeBundle:Default:index$']);
        $signature = serialize([$requestMatcherId, 'fos_http_cache.response_matcher.cacheable']);
        $id = 'fos_http_cache.cache_control.rule_matcher.'.md5($signature);
        $this->assertTrue($container->hasDefinition($id), 'rule matcher not created as expected');

        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.cache_control');
    }

    public function testConfigLoadCacheControlResponseStatus()
    {
        $config = [
            'cache_control' => [
                'rules' => [
                    [
                        'match' => [
                            'methods' => ['GET', 'HEAD'],
                            'additional_response_status' => [500],
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

        $requestMatcherId = $this->assertRequestMatcherCreated($container, []);
        $responseMatcherId = $this->assertResponseCacheableMatcherCreated($container, [500]);
        $signature = serialize([$requestMatcherId, $responseMatcherId]);
        $id = 'fos_http_cache.cache_control.rule_matcher.'.md5($signature);
        $this->assertTrue($container->hasDefinition($id), 'rule matcher not created as expected');

        $this->assertListenerHasRule($container, 'fos_http_cache.event_listener.cache_control');
    }

    public function testConfigLoadCacheControlExpression()
    {
        $config = [
            'cache_control' => [
                'rules' => [
                    [
                        'match' => [
                            'methods' => ['GET', 'HEAD'],
                            'match_response' => 'foobar',
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

        $requestMatcherId = $this->assertRequestMatcherCreated($container, []);
        $responseMatcherId = $this->assertResponseExpressionMatcherCreated($container, 'foobar');
        $signature = serialize([$requestMatcherId, $responseMatcherId]);
        $id = 'fos_http_cache.cache_control.rule_matcher.'.md5($signature);
        $this->assertTrue($container->hasDefinition($id), 'rule matcher not created as expected');

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

        $this->assertRequestMatcherCreated($container, ['_controller' => '^AcmeBundle:Default:index$'], ['GET', 'HEAD']);
    }

    public function testConfigLoadCacheControlDuplicateRule()
    {
        $config = [
            'cache_control' => [
                'rules' => [
                    [
                        'match' => [
                            'methods' => ['GET', 'HEAD'],
                            'match_response' => 'foobar',
                        ],
                        'headers' => [
                            'cache_control' => ['public' => true],
                            'reverse_proxy_ttl' => 42,
                            'vary' => ['Cookie', 'Accept-Language'],
                        ],
                    ],
                    [
                        'match' => [
                            'methods' => ['GET', 'HEAD'],
                            'match_response' => 'foobar',
                        ],
                        'headers' => [
                            'etag' => true,
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->createContainer();
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Duplicate');
        $this->extension->load([$config], $container);
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
        $this->assertFalse($container->has('fos_http_cache.user_context.session_listener'));
    }

    public function testSessionListenerIsDecoratedIfNeeded()
    {
        $config = [[
           'user_context' => [
               'user_identifier_headers' => ['X-Foo'],
               'user_hash_header' => 'X-Bar',
               'hash_cache_ttl' => 30,
               'always_vary_on_context_hash' => true,
               'role_provider' => true,
           ],
       ]];

        $container = $this->createContainer();
        $this->extension->load($config, $container);

        // The decorator is only needed for Symfony 3.4 and 4.0
        // Before 3.4 the cache header is not overwritten.
        // From 4.1 on, we use the AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER
        if (version_compare(Kernel::VERSION, '3.4', '>=')
            && version_compare(Kernel::VERSION, '4.1', '<')
        ) {
            $this->assertTrue($container->hasDefinition('fos_http_cache.user_context.session_listener'));

            $definition = $container->getDefinition('fos_http_cache.user_context.session_listener');

            $this->assertSame('x-bar', $definition->getArgument(1));
            $this->assertSame(['x-foo'], $definition->getArgument(2));
        } else {
            $this->assertFalse($container->hasDefinition('fos_http_cache.user_context.session_listener'));
        }
    }

    public function testConfigLoadFlashMessageListener()
    {
        $config = [
            [
                'flash_message' => true,
            ],
        ];

        $container = $this->createContainer();
        $this->extension->load($config, $container);

        $this->assertTrue($container->has('fos_http_cache.event_listener.flash_message'));
    }

    public function testVarnishDefaultTagMode()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $this->extension->load([$config], $container);

        $this->assertEquals('X-Cache-Tags', $container->getParameter('fos_http_cache.tag_handler.response_header'));
        $this->assertEquals(',', $container->getParameter('fos_http_cache.tag_handler.separator'));
        $this->assertEquals(['tag_mode' => 'ban', 'tags_header' => 'X-Cache-Tags'], $container->getParameter('fos_http_cache.proxy_client.varnish.options'));
    }

    public function testVarnishBanTagMode()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['tag_mode'] = 'ban';
        $this->extension->load([$config], $container);

        $this->assertEquals('X-Cache-Tags', $container->getParameter('fos_http_cache.tag_handler.response_header'));
        $this->assertEquals(',', $container->getParameter('fos_http_cache.tag_handler.separator'));
        $this->assertEquals(['tag_mode' => 'ban', 'tags_header' => 'X-Cache-Tags'], $container->getParameter('fos_http_cache.proxy_client.varnish.options'));
    }

    public function testVarnishBanTagModeOverrides()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['tags_header'] = 'my-tags';
        $config['tags']['response_header'] = 'my-header';
        $config['tags']['separator'] = 'custom';
        $this->extension->load([$config], $container);

        $this->assertEquals('my-header', $container->getParameter('fos_http_cache.tag_handler.response_header'));
        $this->assertEquals('custom', $container->getParameter('fos_http_cache.tag_handler.separator'));
        $this->assertEquals(['tag_mode' => 'ban', 'tags_header' => 'my-tags'], $container->getParameter('fos_http_cache.proxy_client.varnish.options'));
    }

    public function testVarnishXkeyTagMode()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['tag_mode'] = 'purgekeys';
        $this->extension->load([$config], $container);

        $this->assertEquals('xkey', $container->getParameter('fos_http_cache.tag_handler.response_header'));
        $this->assertEquals(' ', $container->getParameter('fos_http_cache.tag_handler.separator'));
        $this->assertEquals(['tag_mode' => 'purgekeys', 'tags_header' => 'xkey-softpurge'], $container->getParameter('fos_http_cache.proxy_client.varnish.options'));
    }

    public function testVarnishXkeyTagModeOverrides()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['tag_mode'] = 'purgekeys';
        $config['proxy_client']['varnish']['tags_header'] = 'my-tags';
        $config['tags']['response_header'] = 'my-header';
        $config['tags']['separator'] = 'custom';
        $this->extension->load([$config], $container);

        $this->assertEquals('my-header', $container->getParameter('fos_http_cache.tag_handler.response_header'));
        $this->assertEquals('custom', $container->getParameter('fos_http_cache.tag_handler.separator'));
        $this->assertEquals(['tag_mode' => 'purgekeys', 'tags_header' => 'my-tags'], $container->getParameter('fos_http_cache.proxy_client.varnish.options'));
    }

    public function testVarnishCustomTagsHeader()
    {
        $container = $this->createContainer();

        $config = $this->getBaseConfig();
        $config['proxy_client']['varnish']['tags_header'] = 'myheader';
        $this->extension->load([$config], $container);

        $this->assertEquals(['tag_mode' => 'ban', 'tags_header' => 'myheader'], $container->getParameter('fos_http_cache.proxy_client.varnish.options'));
    }

    private function createContainer()
    {
        $container = new ContainerBuilder(
            new ParameterBag(['kernel.debug' => false])
        );

        // The cache_manager service depends on the router service
        $container->setDefinition(
            'router',
            new Definition(Router::class)
        );

        return $container;
    }

    private function getBaseConfig()
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
     * @param array            $methods    List of methods for the matcher. Empty array to not check.
     *
     * @return string Service id of the matcher
     */
    private function assertRequestMatcherCreated(ContainerBuilder $container, array $attributes, array $methods = [])
    {
        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if (($definition instanceof DefinitionDecorator
                    || $definition instanceof ChildDefinition)
                && 'fos_http_cache.request_matcher' === $definition->getParent()
            ) {
                if ($matcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $matcherDefinition = $definition;
                $matcherId = $id;
            }
        }

        $this->assertNotNull($matcherDefinition, 'No matcher found');

        if ($methods) {
            // 3rd argument should be the request methods
            $this->assertEquals($methods, $matcherDefinition->getArgument(2));
        }
        // 5th argument should contain the request attribute criteria
        $this->assertEquals($attributes, $matcherDefinition->getArgument(4));

        return $matcherId;
    }

    /**
     * @param ContainerBuilder $container
     * @param int[]            $additionalStatus
     *
     * @return DefinitionDecorator|ChildDefinition
     */
    private function assertResponseCacheableMatcherCreated(ContainerBuilder $container, array $additionalStatus)
    {
        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if (($definition instanceof DefinitionDecorator
                    || $definition instanceof ChildDefinition)
                && 'fos_http_cache.response_matcher.cache_control.cacheable_response' === $definition->getParent()
            ) {
                if ($matcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $matcherDefinition = $definition;
                $matcherId = $id;
            }
        }

        $this->assertNotNull($matcherDefinition, 'No matcher found');
        $this->assertEquals($additionalStatus, $matcherDefinition->getArgument(0));

        return $matcherId;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $expression
     *
     * @return DefinitionDecorator|ChildDefinition
     */
    private function assertResponseExpressionMatcherCreated(ContainerBuilder $container, $expression)
    {
        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if (($definition instanceof DefinitionDecorator
                    || $definition instanceof ChildDefinition)
                && 'fos_http_cache.response_matcher.cache_control.expression' === $definition->getParent()
            ) {
                if ($matcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $matcherDefinition = $definition;
                $matcherId = $id;
            }
        }

        $this->assertNotNull($matcherDefinition, 'No matcher found');
        $this->assertEquals($expression, $matcherDefinition->getArgument(0));

        return $matcherId;
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
