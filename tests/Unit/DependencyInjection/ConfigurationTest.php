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

use FOS\HttpCacheBundle\DependencyInjection\Configuration;
use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension()
    {
        return new FOSHttpCacheExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration(false);
    }

    public function testEmptyConfiguration()
    {
        $expectedConfiguration = $this->getEmptyConfig();

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/empty.yml',
            'config/empty.xml',
            'config/empty.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = [
            'cacheable' => [
                'response' => [
                    'additional_status' => [100, 500],
                    'expression' => null,
                ],
            ],
            'cache_control' => [
                'defaults' => [
                    'overwrite' => true,
                ],
                'rules' => [
                    [
                        'match' => [
                            'path' => '/abc',
                            'host' => 'fos',
                            'methods' => ['GET', 'POST'],
                            'ips' => ['1.2.3.4', '1.1.1.1'],
                            'attributes' => ['_controller' => 'fos.user_bundle.*'],
                            'match_response' => 'response.getStatusCode() == 404',
                            'additional_response_status' => [],
                        ],
                        'headers' => [
                            'overwrite' => false,
                            'cache_control' => [
                                'max_age' => 1,
                                's_maxage' => 2,
                                'public' => true,
                                'must_revalidate' => true,
                                'proxy_revalidate' => false,
                                'no_transform' => true,
                                'no_cache' => false,
                                'stale_if_error' => 3,
                                'stale_while_revalidate' => 4,
                            ],
                            'etag' => true,
                            'last_modified' => '-1 hour',
                            'reverse_proxy_ttl' => 42,
                            'vary' => ['Cookie', 'Authorization'],
                        ],
                    ],
                ],
            ],
            'proxy_client' => [
                'varnish' => [
                    'tags_header' => 'My-Cache-Tags',
                    'header_length' => 1234,
                    'default_ban_headers' => ['Foo' => 'Bar'],
                    'http' => [
                        'servers' => ['22.22.22.22'],
                        'base_url' => '/test',
                        'http_client' => 'acme.guzzle.varnish',
                    ],
                ],
            ],
            'cache_manager' => [
                'enabled' => true,
                'custom_proxy_client' => 'acme.proxy_client',
                'generate_url_type' => 'auto',
            ],
            'tags' => [
                'enabled' => 'auto',
                'strict' => false,
                'response_header' => 'FOS-Tags',
                'expression_language' => 'acme.expression_language',
                'rules' => [
                    [
                        'match' => [
                            'path' => '/def',
                            'host' => 'friends',
                            'methods' => ['PUT', 'DELETE'],
                            'ips' => ['99.99.99.99'],
                            'attributes' => [
                                '_foo' => 'bar',
                            ],
                        ],
                        'tags' => ['a', 'b'],
                        'tag_expressions' => ['"a"', '"b"'],
                    ],
                ],
            ],
            'invalidation' => [
                'enabled' => 'auto',
                'expression_language' => 'acme.expression_language',
                'rules' => [
                    [
                        'match' => [
                            'path' => '/hij',
                            'host' => 'symfony',
                            'methods' => ['PATCH'],
                            'ips' => ['42.42.42.42'],
                            'attributes' => [
                                '_format' => 'json',
                            ],
                        ],
                        'routes' => [
                            'invalidate_route1' => [
                                'ignore_extra_params' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'user_context' => [
                'enabled' => true,
                'match' => [
                    'matcher_service' => 'fos_http_cache.user_context.request_matcher',
                    'accept' => 'application/vnd.fos.user-context-hash',
                    'method' => 'GET',
                ],
                'hash_cache_ttl' => 300,
                'always_vary_on_context_hash' => true,
                'user_identifier_headers' => ['Cookie', 'Authorization'],
                'user_hash_header' => 'FOS-User-Context-Hash',
                'role_provider' => true,
                'logout_handler' => [
                    'enabled' => 'auto',
                ],
            ],
            'flash_message' => [
                'enabled' => true,
                'name' => 'flashtest',
                'path' => '/x',
                'host' => 'y',
                'secure' => true,
            ],
            'debug' => [
                'enabled' => true,
                'header' => 'FOS-Cache-Debug',
            ],
        ];

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/full.yml',
            'config/full.xml',
            'config/full.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testCustomProxyClient()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['cache_manager'] = [
            'enabled' => true,
            'custom_proxy_client' => 'acme.proxy_client',
            'generate_url_type' => 'auto',
        ];
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/custom-client.yml',
            'config/custom-client.xml',
            'config/custom-client.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testSupportsNginx()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['proxy_client'] = [
            'nginx' => [
                'purge_location' => '/purge',
                'http' => [
                    'servers' => ['22.22.22.22'],
                    'base_url' => '/test',
                    'http_client' => 'acme.guzzle.nginx',
                ],
            ],
        ];
        $expectedConfiguration['cache_manager']['enabled'] = 'auto';
        $expectedConfiguration['cache_manager']['generate_url_type'] = 'auto';
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';
        $expectedConfiguration['user_context']['logout_handler']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/nginx.yml',
            'config/nginx.xml',
            'config/nginx.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testSupportsSymfony()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['proxy_client'] = [
            'symfony' => [
                'http' => [
                    'servers' => ['22.22.22.22'],
                    'base_url' => '/test',
                    'http_client' => 'acme.guzzle.symfony',
                ],
            ],
        ];
        $expectedConfiguration['cache_manager']['enabled'] = 'auto';
        $expectedConfiguration['cache_manager']['generate_url_type'] = 'auto';
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';
        $expectedConfiguration['user_context']['logout_handler']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/symfony.yml',
            'config/symfony.xml',
            'config/symfony.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testSupportsNoop()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['proxy_client'] = [
            'noop' => true,
        ];
        $expectedConfiguration['cache_manager']['enabled'] = 'auto';
        $expectedConfiguration['cache_manager']['generate_url_type'] = 'auto';
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';
        $expectedConfiguration['user_context']['logout_handler']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/noop.yml',
            'config/noop.xml',
            'config/noop.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testSplitOptions()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['cache_control'] = [
            'rules' => [
                [
                    'match' => [
                        'path' => null,
                        'host' => null,
                        'attributes' => [],
                        'methods' => ['GET', 'POST'],
                        'ips' => ['1.2.3.4', '1.1.1.1'],
                        'additional_response_status' => [],
                        'match_response' => null,
                    ],
                    'headers' => [
                        'reverse_proxy_ttl' => null,
                        'vary' => ['Cookie', 'Authorization'],
                        'overwrite' => 'default',
                        'etag' => false,
                    ],
                ],
            ],
            'defaults' => [
                'overwrite' => false,
            ],
        ];
        $expectedConfiguration['proxy_client'] = [
            'varnish' => [
                'http' => [
                    'base_url' => null,
                    'http_client' => null,
                    'servers' => ['1.1.1.1:80', '2.2.2.2:80'],
                ],
                'tags_header' => 'X-Cache-Tags',
            ],
            'nginx' => [
                'purge_location' => false,
                'http' => [
                    'base_url' => null,
                    'http_client' => null,
                    'servers' => ['1.1.1.1:81', '2.2.2.2:81'],
                ],
            ],
        ];
        $expectedConfiguration['cache_manager']['enabled'] = 'auto';
        $expectedConfiguration['cache_manager']['generate_url_type'] = 'auto';
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';
        $expectedConfiguration['user_context']['logout_handler']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/split.yml',
            'config/split.xml',
            'config/split.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testSupportsCacheableResponseExpression()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['cacheable']['response']['expression']
            = 'response.getStatusCode() >= 300';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/cacheable_response_expression.yml',
            'config/cacheable_response_expression.xml',
            'config/cacheable_response_expression.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testCacheManagerNoClient()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/cachemanager_noclient.yml',
            'config/cachemanager_noclient.xml',
            'config/cachemanager_noclient.php',
        ]);

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals([], [$format]);
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('need to configure a proxy_client', $e->getMessage());
            }
        }
    }

    public function testTagsNoCacheManager()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/tags_nocachemanager.yml',
            'config/tags_nocachemanager.xml',
            'config/tags_nocachemanager.php',
        ]);

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals([], [$format]);
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('cache_manager needed for tag handling', $e->getMessage());
            }
        }
    }

    public function testTagsStrict()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['tags']['strict'] = true;

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/tags_strict.yml',
            'config/tags_strict.xml',
            'config/tags_strict.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testInvalidationNoCacheManager()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/invalidation_nocachemanager.yml',
            'config/invalidation_nocachemanager.xml',
            'config/invalidation_nocachemanager.php',
        ]);

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals([], [$format]);
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('cache_manager needed for invalidation handling', $e->getMessage());
            }
        }
    }

    public function testInvalidDate()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/invalid_date.yml',
            'config/invalid_date.xml',
            'config/invalid_date.php',
        ]);

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals([], [$format]);
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('Failed to parse time string', $e->getMessage());
            }
        }
    }

    /**
     * @return array The configuration when nothing is specified
     */
    private function getEmptyConfig()
    {
        return [
            'cacheable' => [
                'response' => [
                    'additional_status' => [],
                    'expression' => null,
                ],
            ],
            'cache_manager' => [
                'enabled' => false,
                'generate_url_type' => 'auto',
            ],
            'tags' => [
                'enabled' => false,
                'strict' => false,
                'response_header' => 'X-Cache-Tags',
                'expression_language' => null,
                'rules' => [],
            ],
            'invalidation' => [
                'enabled' => false,
                'expression_language' => null,
                'rules' => [],
            ],
            'user_context' => [
                'enabled' => false,
                'match' => [
                    'matcher_service' => 'fos_http_cache.user_context.request_matcher',
                    'accept' => 'application/vnd.fos.user-context-hash',
                    'method' => null,
                ],
                'hash_cache_ttl' => 0,
                'always_vary_on_context_hash' => true,
                'user_identifier_headers' => ['Cookie', 'Authorization'],
                'user_hash_header' => 'X-User-Context-Hash',
                'role_provider' => false,
                'logout_handler' => [
                    'enabled' => false,
                ],
            ],
            'flash_message' => [
                'enabled' => false,
                'name' => 'flashes',
                'path' => '/',
                'host' => null,
                'secure' => false,
            ],
            'debug' => [
                'enabled' => false,
                'header' => 'X-Cache-Debug',
            ],
        ];
    }
}
