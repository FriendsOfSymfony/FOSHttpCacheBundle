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
use JeanBeru\HttpCacheCloudFront\Proxy\CloudFront;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\ResolveEnvPlaceholdersPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveParameterPlaceHoldersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\Router;

class FOSHttpCacheExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FOSHttpCacheExtension
     */
    protected $extension;

    protected function setUp(): void
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

    public function testConfigLoadVarnishInvalidUrl()
    {
        $this->expectException(InvalidConfigurationException::class);

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

    public function testConfigLoadCloudflare()
    {
        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'cloudflare' => [
                        'authentication_token' => 'test',
                        'zone_identifier' => 'test',
                    ],
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.cloudflare'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
    }

    public function testConfigLoadCloudfront()
    {
        if (!class_exists(CloudFront::class)) {
            $this->markTestSkipped('jean-beru/fos-http-cache-cloudfront not available');
        }

        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'cloudfront' => [
                        'distribution_id' => 'my_distribution',
                    ],
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.cloudfront.cloudfront_client'));
        $this->assertTrue($container->hasParameter('fos_http_cache.proxy_client.cloudfront.options'));
        $this->assertSame(['distribution_id' => 'my_distribution'], $container->getParameter('fos_http_cache.proxy_client.cloudfront.options'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.cloudfront'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
    }

    public function testConfigLoadCloudfrontWithClient()
    {
        if (!class_exists(CloudFront::class)) {
            $this->markTestSkipped('jean-beru/fos-http-cache-cloudfront not available');
        }

        $container = $this->createContainer();
        $this->extension->load([
            [
                'proxy_client' => [
                    'cloudfront' => [
                        'distribution_id' => 'my_distribution',
                        'client' => 'my.client',
                    ],
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasAlias('fos_http_cache.proxy_client.cloudfront.cloudfront_client'));
        $this->assertTrue($container->hasParameter('fos_http_cache.proxy_client.cloudfront.options'));
        $this->assertSame(['distribution_id' => 'my_distribution'], $container->getParameter('fos_http_cache.proxy_client.cloudfront.options'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.cloudfront'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
    }

    public function testConfigLoadFastly()
    {
        $container = $this->createContainer();
        $this->extension->load([
                                   [
                                       'proxy_client' => [
                                           'fastly' => [
                                               'service_identifier' => 'test',
                                               'authentication_token' => 'test',
                                           ],
                                       ],
                                   ],
                               ], $container);

        $this->assertFalse($container->hasDefinition('fos_http_cache.proxy_client.varnish'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.proxy_client.fastly'));
        $this->assertTrue($container->hasAlias('fos_http_cache.default_proxy_client'));
        $this->assertTrue($container->hasDefinition('fos_http_cache.event_listener.invalidation'));
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

    public function testConfigTagNotSupported()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You can not enable cache tagging with the nginx client');

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

    /**
     * @param array|null        $serversValue            array that contains servers, `null` if not set
     * @param string|null       $serversFromJsonEnvValue string that should contain an env var (use `VARNISH_SERVERS` for this test), `null` if not set
     * @param string|mixed|null $envValue                _ENV['VARNISH_SERVERS'] will be set to this value; only used if `$serversFromJsonEnvValue` is used; should be a string, otherwise an error will show up
     * @param array|null        $expectedServersValue    expected servers value the http dispatcher receives
     * @param string|null       $expectExceptionClass    the exception class the configuration might throw, `null` if no exception is thrown
     * @param string|null       $expectExceptionMessage  the message the exception throws, anything if no exception is thrown
     *
     * @dataProvider dataVarnishServersConfig
     */
    public function testVarnishServersConfig($serversValue, $serversFromJsonEnvValue, $envValue, $expectedServersValue, $expectExceptionClass, $expectExceptionMessage): void
    {
        $_ENV['VARNISH_SERVERS'] = $envValue;
        $container = $this->createContainer();

        // workaround to get the possible env string into the EnvPlaceholderParameterBag
        $container->setParameter('triggerServersValue', $serversValue);
        $container->setParameter('triggerServersFromJsonEnvValue', $serversFromJsonEnvValue);
        (new ResolveParameterPlaceHoldersPass())->process($container);

        $config = $this->getBaseConfig();

        if (null === $serversValue) {
            unset($config['proxy_client']['varnish']['http']['servers']);
        } else {
            $config['proxy_client']['varnish']['http']['servers'] = $container->getParameter('triggerServersValue');
        }
        if (null !== $serversFromJsonEnvValue) {
            $config['proxy_client']['varnish']['http']['servers_from_jsonenv'] = $container->getParameter('triggerServersFromJsonEnvValue');
        }

        if ($expectExceptionClass) {
            $this->expectException($expectExceptionClass);
            $this->expectExceptionMessage($expectExceptionMessage);
        }

        $this->extension->load([$config], $container);

        // Note: until here InvalidConfigurationException should be thrown
        if (InvalidConfigurationException::class === $expectExceptionClass) {
            return;
        }

        (new ResolveEnvPlaceholdersPass())->process($container);

        // Note: now all expected exceptions should be thrown
        if ($expectExceptionClass) {
            return;
        }

        $definition = $container->getDefinition('fos_http_cache.proxy_client.varnish.http_dispatcher');
        static::assertEquals($expectedServersValue, $definition->getArgument(0));
    }

    public function dataVarnishServersConfig()
    {
        return [
            // working case before implementing the feature 'env vars in servers key'
            'regular array as servers value allowed' => [['my-server-1', 'my-server-2'], null, null, ['my-server-1', 'my-server-2'], null, null],
            // testing the feature 'env vars in servers_from_jsonenv key'
            'env var with json array as servers value allowed' => [null, '%env(json:VARNISH_SERVERS)%', '["my-server-1","my-server-2"]', ['my-server-1', 'my-server-2'], null, null],
            // not allowed cases (servers_from_jsonenv)
            'plain string as servers value is forbidden' => [null, 'plain_string_not_allowed_as_servers_from_jsonenv_value', null, null, InvalidConfigurationException::class, 'Not a valid Varnish servers_from_jsonenv configuration: plain_string_not_allowed_as_servers_from_jsonenv_value'],
            'an int as servers value is forbidden' => [null, 1, 'env_value_not_used', null, InvalidConfigurationException::class, 'The "http.servers" or "http.servers_from_jsonenv" section must be defined for the proxy "varnish"'],
            'env var with string as servers value is forbidden (at runtime)' => [null, '%env(json:VARNISH_SERVERS)%', 'wrong_usage_of_env_value', 'no_servers_value', RuntimeException::class, 'Invalid JSON in env var "VARNISH_SERVERS": Syntax error'],
            // more cases
            'no definition leads to error' => [null, null, 'not_used', 'not_used', InvalidConfigurationException::class, 'The "http.servers" or "http.servers_from_jsonenv" section must be defined for the proxy "varnish"'],
            'both servers and servers_from_jsonenv defined leads to error' => [['my-server-1', 'my-server-2'], '%env(json:VARNISH_SERVERS)%', 'not_used', 'not_used', InvalidConfigurationException::class, 'You can only set one of "http.servers" or "http.servers_from_jsonenv" but not both to avoid ambiguity for the proxy "varnish"'],
        ];
    }

    private function createContainer()
    {
        $container = new ContainerBuilder(
            new EnvPlaceholderParameterBag(['kernel.debug' => false])
        );

        // The cache_manager service depends on the router service
        $container->setDefinition(
            'router',
            new Definition(Router::class)
        );

        // The AttributesListener depends on the controller_resolver
        $container->setDefinition(
            'controller_resolver',
            new Definition(ControllerResolverInterface::class)
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
     * @param array $methods List of methods for the matcher. Empty array to not check.
     *
     * @return string|null Service id of the matcher
     */
    private function assertRequestMatcherCreated(ContainerBuilder $container, array $attributes, array $methods = []): ?string
    {
        // Extract the corresponding definition
        $chainMatcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition instanceof ChildDefinition
                && 'fos_http_cache.request_matcher' === $definition->getParent()
            ) {
                if ($chainMatcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $chainMatcherDefinition = $definition;
                $matcherId = $id;
            }
        }

        $this->assertNotNull($chainMatcherDefinition, 'No matcher found');

        $matchers = $chainMatcherDefinition->getArgument(0);
        foreach ($matchers as $matcherReference) {
            $this->assertInstanceOf(Reference::class, $matcherReference);
            $matcherDefinition = $container->getDefinition((string) $matcherReference);
            if (str_starts_with((string) $matcherReference, 'fos_http_cache.request_matcher.attributes')) {
                $this->assertEquals($attributes, $matcherDefinition->getArgument(0));
            }
            if ($methods && str_starts_with((string) $matcherReference, 'fos_http_cache.request_matcher.methods')) {
                $this->assertEquals($methods, $matcherDefinition->getArgument(0));
            }
        }

        return $matcherId;
    }

    /**
     * @param int[] $additionalStatus
     *
     * @return string
     */
    private function assertResponseCacheableMatcherCreated(ContainerBuilder $container, array $additionalStatus)
    {
        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition instanceof ChildDefinition
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
     * @param string $expression
     *
     * @return string
     */
    private function assertResponseExpressionMatcherCreated(ContainerBuilder $container, $expression)
    {
        // Extract the corresponding definition
        $matcherDefinition = null;
        $matcherId = null;
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition instanceof ChildDefinition
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
     * @param string $id The service id to investigate
     */
    private function assertListenerHasRule(ContainerBuilder $container, $id)
    {
        $this->assertTrue($container->hasDefinition($id));
        $listener = $container->getDefinition($id);
        $this->assertTrue($listener->hasMethodCall('addRule'));
        $this->assertCount(1, $listener->getMethodCalls());
    }
}
