<?php

namespace FOS\HttpCacheBundle\Test;

use FOS\HttpCache\Test\PHPUnit\IsCacheHitConstraint;
use FOS\HttpCache\Test\PHPUnit\IsCacheMissConstraint;
use FOS\HttpCache\Test\Proxy\ProxyInterface;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class that you can extend to run integration tests against a live
 * caching proxy instance
 */
abstract class ProxyTestCase extends WebTestCase
{
    /**
     * Assert cache hit
     *
     * @param Response    $response
     * @param string|null $message
     */
    public function assertHit(Response $response, $message = null)
    {
        self::assertThat($response, self::isCacheHit(), $message);
    }

    /**
     * Assert cache miss
     *
     * @param Response    $response
     * @param string|null $message
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
     * Get HTTP test client for making requests to your application through a
     * live caching proxy
     *
     * @return ClientInterface
     */
    protected function getHttpClient()
    {
        return static::getContainer()->get('fos_http_cache.test.default_client');
    }

    /**
     * Get a response from your application through a live caching proxy
     *
     * @param string $url     Request URL (absolute or relative)
     * @param array  $headers Request HTTP headers
     * @param array  $options Request options
     *
     * @return Response
     */
    protected function getResponse($url, array $headers = array(), $options = array())
    {
        return $this->getHttpClient()->get($url, $headers, $options)->send();
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
     * @return ProxyInterface
     *
     * @throws \RuntimeException If proxy server is not configured
     */
    protected function getProxy()
    {
        if (!static::getContainer()->has('fos_http_cache.test.default_proxy_server')) {
            throw new \RuntimeException(
                'Proxy server is not available. Please configure a proxy_server '
                .'under test in your application config.'
            );
        }

        return static::getContainer()->get('fos_http_cache.test.default_proxy_server');
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
     * @return ContainerInterface
     */
    protected static function getContainer()
    {
        return static::createClient()->getContainer();
    }
}
