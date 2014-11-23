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
        }, array(
            'config/empty.yml',
            'config/empty.xml',
            'config/empty.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }

    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = array(
            'cache_control' => array(
                'defaults' => array(
                    'overwrite' => true,
                ),
                'rules' => array(
                    array(
                        'match' => array(
                            'path' => '/abc',
                            'host' => 'fos',
                            'methods' => array('GET', 'POST'),
                            'ips' => array('1.2.3.4', '1.1.1.1'),
                            'attributes' => array('_controller' => 'fos.user_bundle.*'),
                            'additional_cacheable_status' => array(100, 500),
                            'match_response' => '',
                            // TODO 'match_response' => '',
                        ),
                        'headers' => array(
                            'overwrite' => false,
                            'cache_control' => array(
                                'max_age' => 1,
                                's_maxage' => 2,
                                'public' => true,
                                'must_revalidate' => true,
                                'proxy_revalidate' => false,
                                'no_transform' => true,
                                'no_cache' => false,
                                'stale_if_error' => 3,
                                'stale_while_revalidate' => 4,
                            ),
                            'last_modified' => '-1 hour',
                            'reverse_proxy_ttl' => 42,
                            'vary' => array('Cookie', 'Authorization'),
                        ),
                    ),
                ),
            ),
            'proxy_client' => array(
                'varnish' => array(
                    'servers' => array('22.22.22.22'),
                    'base_url' => '/test',
                    'guzzle_client' => 'acme.guzzle.varnish',
                ),
            ),
            'cache_manager' => array(
                'enabled' => true,
            ),
            'tags' => array(
                'enabled' => 'auto',
                'header' => 'FOS-Tags',
                'rules' => array(
                    array(
                        'match' => array(
                            'path' => '/def',
                            'host' => 'friends',
                            'methods' => array('PUT', 'DELETE'),
                            'ips' => array('99.99.99.99'),
                            'attributes' => array(
                                '_foo' => 'bar',
                            ),
                            'additional_cacheable_status' => array(501, 502),
                            'match_response' => '',
                            // TODO match_response
                        ),
                        'tags' => array('a', 'b'),
                        'tag_expressions' => array('"a"', '"b"'),
                    ),
                ),
            ),
            'invalidation' => array(
                'enabled' => 'auto',
                'rules' => array(
                    array(
                        'match' => array(
                            'path' => '/hij',
                            'host' => 'symfony',
                            'methods' => array('PATCH'),
                            'ips' => array('42.42.42.42'),
                            'attributes' => array(
                                '_format' => 'json',
                            ),
                            'additional_cacheable_status' => array(404, 403),
                            'match_response' => '',
                            // TODO match_response
                        ),
                        'routes' => array(
                            'invalidate_route1' => array(
                                'ignore_extra_params' => false,
                            ),
                        ),
                    ),
                ),
            ),
            'user_context' => array(
                'enabled' => true,
                'match' => array(
                    'matcher_service' => 'fos_http_cache.user_context.request_matcher',
                    'accept' => 'application/vnd.fos.user-context-hash',
                    'method' => 'GET',
                ),
                'hash_cache_ttl' => 300,
                'user_identifier_headers' => array('Cookie', 'Authorization'),
                'user_hash_header' => 'FOS-User-Context-Hash',
                'role_provider' => true,
                'logout_handler' => array(
                    'enabled' => 'auto',
                ),
            ),
            'flash_message' => array(
                'enabled' => true,
                'name' => 'flashtest',
                'path' => '/x',
                'host' => 'y',
                'secure' => true,
            ),
            'debug' => array(
                'enabled' => true,
                'header' => 'FOS-Cache-Debug',
            ),
        );

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/full.yml',
            'config/full.xml',
            'config/full.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }

    public function testSupportsNginx()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['proxy_client'] = array(
            'nginx' => array(
                'servers' => array('22.22.22.22'),
                'base_url' => '/test',
                'guzzle_client' => 'acme.guzzle.nginx',
                'purge_location' => '/purge',
            ),
        );
        $expectedConfiguration['cache_manager']['enabled'] = 'auto';
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';
        $expectedConfiguration['user_context']['logout_handler']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/nginx.yml',
            'config/nginx.xml',
            'config/nginx.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }

    public function testSplitOptions()
    {
        $expectedConfiguration = $this->getEmptyConfig();
        $expectedConfiguration['cache_control'] = array(
            'rules' => array(
                array(
                    'match' => array(
                        'path' => null,
                        'host' => null,
                        'attributes' => array(),
                        'additional_cacheable_status' => array(),
                        'match_response' => null,
                        'methods' => array('GET', 'POST'),
                        'ips' => array('1.2.3.4', '1.1.1.1'),
                    ),
                    'headers' => array(
                        'reverse_proxy_ttl' => null,
                        'vary' => array('Cookie', 'Authorization'),
                        'overwrite' => 'default',
                    ),
                ),
            ),
            'defaults' => array(
                'overwrite' => false,
            ),
        );
        $expectedConfiguration['proxy_client'] = array(
            'varnish' => array(
                'base_url' => null,
                'guzzle_client' => null,
                'servers' => array('1.1.1.1:80', '2.2.2.2:80'),
            ),
            'nginx' => array(
                'base_url' => null,
                'guzzle_client' => null,
                'purge_location' => '',
                'servers' => array('1.1.1.1:81', '2.2.2.2:81'),
            ),
        );
        $expectedConfiguration['cache_manager']['enabled'] = 'auto';
        $expectedConfiguration['tags']['enabled'] = 'auto';
        $expectedConfiguration['invalidation']['enabled'] = 'auto';
        $expectedConfiguration['user_context']['logout_handler']['enabled'] = 'auto';

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/split.yml',
            'config/split.xml',
            'config/split.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }

    public function testCacheManagerNoClient()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/cachemanager_noclient.yml',
            'config/cachemanager_noclient.xml',
            'config/cachemanager_noclient.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
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
        }, array(
            'config/tags_nocachemanager.yml',
            'config/tags_nocachemanager.xml',
            'config/tags_nocachemanager.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('cache_manager needed for tag handling', $e->getMessage());
            }
        }
    }

    public function testInvalidationNoCacheManager()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/invalidation_nocachemanager.yml',
            'config/invalidation_nocachemanager.xml',
            'config/invalidation_nocachemanager.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('cache_manager needed for invalidation handling', $e->getMessage());
            }
        }
    }

    public function testTagRulesNotEnabled()
    {

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/tags_rules.yml',
            'config/tags_rules.xml',
            'config/tags_rules.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('need to enable the cache_manager and tags to use rules', $e->getMessage());
            }
        }
    }

    public function testInvalidationRulesNotEnabled()
    {

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/invalidation_rules.yml',
            'config/invalidation_rules.xml',
            'config/invalidation_rules.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('need to enable the cache_manager and invalidation to use rules', $e->getMessage());
            }
        }
    }

    public function testInvalidDate()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/invalid_date.yml',
            'config/invalid_date.xml',
            'config/invalid_date.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('Failed to parse time string', $e->getMessage());
            }
        }
    }

    /**
     * The configuration is reused, we only need to test this once.
     */
    public function testRulesBothStatusAndExpression()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/rules_matchstatusandexpression.yml',
            'config/rules_matchstatusandexpression.xml',
            'config/rules_matchstatusandexpression.php',
        ));

        foreach ($formats as $format) {
            try {
                $this->assertProcessedConfigurationEquals(array(), array($format));
                $this->fail('No exception thrown on invalid configuration');
            } catch (InvalidConfigurationException $e) {
                $this->assertContains('may not set both additional_cacheable_status and match_response', $e->getMessage());
            }
        }
    }

    /**
     * @return array The configuration when nothing is specified.
     */
    private function getEmptyConfig()
    {
        return array(
            'cache_manager' => array(
                'enabled' => false,
            ),
            'tags' => array(
                'enabled' => false,
                'header' => 'X-Cache-Tags',
                'rules' => array(),
            ),
            'invalidation' => array(
                'enabled' => false,
                'rules' => array(),
            ),
            'user_context' => array(
                'enabled' => false,
                'match' => array(
                    'matcher_service' => 'fos_http_cache.user_context.request_matcher',
                    'accept' => 'application/vnd.fos.user-context-hash',
                    'method' => null,
                ),
                'hash_cache_ttl' => 0,
                'user_identifier_headers' => array('Cookie', 'Authorization'),
                'user_hash_header' => 'X-User-Context-Hash',
                'role_provider' => false,
                'logout_handler' => array(
                    'enabled' => false
                ),
            ),
            'flash_message' => array(
                'enabled' => false,
                'name' => 'flashes',
                'path' => '/',
                'host' => null,
                'secure' => false,
            ),
            'debug' => array(
                'enabled' => false,
                'header' => 'X-Cache-Debug',
            ),
        );
    }
}
