<?php

namespace FOS\HttpCacheBundle\Test;

use FOS\HttpCache\Test\PHPUnit\IsCacheHitConstraint;
use FOS\HttpCache\Test\PHPUnit\IsCacheMissConstraint;
use FOS\HttpCache\Test\ProxyTestCaseInterface;
use Guzzle\Http\Message\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class that you can extend to run integration tests against a live
 * caching proxy instance
 */
abstract class ProxyTestCase extends WebTestCase
{
    /**
     * Assert cache hit
     *
     * @param Response $response
     * @param string   $message
     */
    public function assertHit(Response $response, $message = null)
    {
        self::assertThat($response, self::isCacheHit(), $message);
    }

    /**
     * Assert cache miss
     *
     * @param Response $response
     * @param string   $message
     */
    public function assertMiss(Response $response, $message = null)
    {
        self::assertThat($response, self::isCacheMiss(), $message);
    }

    /**
     * Get cache hit constraint
     *
     * @return IsCacheHitConstraint
     */
    public static function isCacheHit()
    {
        return new IsCacheHitConstraint(self::getCacheDebugHeader());
    }

    /**
     * Get cache miss constraint
     *
     * @return IsCacheMissConstraint
     */
    public static function isCacheMiss()
    {
        return new IsCacheMissConstraint(self::getCacheDebugHeader());
    }

    /**
     * Get test client
     *
     * @return \Guzzle\Http\Client
     */
    public function getClient()
    {
        return static::getContainer()->get('fos_http_cache.test.default_client');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse($url, array $headers = array(), $options = array())
    {
        return $this->getClient()->get($url, $headers, $options)->send();
    }

    /**
     * Start and clear caching proxy server if test is annotated with @clearCache
     */
    protected function setUp()
    {
        $annotations = \PHPUnit_Util_Test::parseTestMethodAnnotations(
            get_class($this),
            $this->getName()
        );

        if (isset($annotations['class']['clearCache'])
            || isset($annotations['method']['clearCache'])
        ) {
            $this->getProxy()->clear();
        }
    }

    /**
     * Get proxy server
     *
     * @return \FOS\HttpCache\Test\Proxy\ProxyInterface
     *
     * @throws \RuntimeException If proxy server is not configured
     */
    protected function getProxy()
    {
        if (!static::getContainer()->has('fos_http_cache.test.default_proxy_server')) {
            throw new \RuntimeException(
                'Proxy server is not available. Please configure a proxy_server '
                . 'under test in your application config.'
            );
        }
        return static::getContainer()->get('fos_http_cache.test.default_proxy_server');
    }

    /**
     * Get default caching proxy client
     *
     * @return \FOS\HttpCache\ProxyClient\ProxyClientInterface
     */
    protected function getProxyClient()
    {
        return static::getContainer()->get('fos_http_cache.default_proxy_client');
    }

    /**
     * Get HTTP header that shows whether the response was a cache hit or miss
     *
     * @return string
     */
    protected static function getCacheDebugHeader()
    {
        return static::getContainer()->getParameter('fos_http_cache.test.cache_header');
    }

    /**
     * Get container
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected static function getContainer()
    {
        return static::createClient()->getContainer();
    }
}
