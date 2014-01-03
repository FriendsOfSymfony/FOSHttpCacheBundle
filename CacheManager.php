<?php

namespace FOS\HttpCacheBundle;

use FOS\HttpCacheBundle\Invalidation\CacheProxyInterface;
use FOS\HttpCacheBundle\Invalidation\Method\BanInterface;
use FOS\HttpCacheBundle\Invalidation\Method\PurgeInterface;
use FOS\HttpCacheBundle\Invalidation\Method\RefreshInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * Manages HTTP cache invalidations
 *
 */
class CacheManager
{
    /**
     * @var string
     */
    protected $tagsHeader = 'X-Cache-Tags';

    /**
     * @var CacheProxyInterface
     */
    protected $cache;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Constructor
     *
     * @param CacheProxyInterface $cache  HTTP cache
     * @param RouterInterface     $router Symfony router
     */
    public function __construct(CacheProxyInterface $cache, RouterInterface $router)
    {
        $this->cache = $cache;
        $this->router = $router;
    }

    /**
     * Set the HTTP header name that will hold cache tags
     *
     * @param string $tagsHeader
     */
    public function setTagsHeader($tagsHeader)
    {
        $this->tagsHeader = $tagsHeader;
    }

    /**
     * Get the HTTP header name that will hold cache tags
     *
     * @return string
     */
    public function getTagsHeader()
    {
        return $this->tagsHeader;
    }

    /**
     * Assign cache tags to a response
     *
     * @param Response $response
     * @param array    $tags
     * @param bool     $replace  Whether to replace the current tags on the
     *                           response
     *
     * @return $this
     */
    public function tagResponse(Response $response, array $tags, $replace = false)
    {
        if (!$replace) {
            $tags = array_merge(
                $response->headers->get($this->getTagsHeader(), array()),
                $tags
            );
        }

        $uniqueTags = array_unique($tags);
        $response->headers->set($this->getTagsHeader(), implode(',', $uniqueTags));

        return $this;
    }

    /**
     * Invalidate a path or URL
     *
     * @param string $path Path or URL
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function invalidatePath($path)
    {
        if (!$this->cache instanceof PurgeInterface) {
            throw new \RuntimeException('HTTP cache does not support PURGE requests');
        }

        return $this->cache->purge($path);

        return $this;
    }

    /**
     * Invalidate a route
     *
     * @param string $name       Route name
     * @param array  $parameters Route parameters (optional)
     *
     * @return $this
     */
    public function invalidateRoute($name, $parameters = array())
    {
        $this->invalidatePath($this->router->generate($name, $parameters));

        return $this;
    }

    /**
     * Refresh a path or URL
     *
     * @param string $path   Path or URL
     * @param array $headers HTTP headers (optional)
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function refreshPath($path, array $headers = array())
    {
        if (!$this->cache instanceof RefreshInterface) {
            throw new \RuntimeException('HTTP cache does not support refresh requests');
        }

        $this->cache->refresh($path, $headers);

        return $this;
    }

    /**
     * Refresh a route
     *
     * @param string $route     Route name
     * @param array $parameters Route parameters (optional)
     *
     * @return $this
     */
    public function refreshRoute($route, array $parameters = array())
    {
        $this->refreshPath($this->router->generate($route, $parameters));

        return $this;
    }



    public function invalidateRegex($regex)
    {

    }

    /**
     * Invalidate cache tags
     *
     * @param array $tags Cache tags
     *
     * @return $this
     * @throws \RuntimeException If HTTP cache does not support BAN requests
     */
    public function invalidateTags(array $tags)
    {
        if (!$this->cache instanceof BanInterface) {
            throw new \RuntimeException('HTTP cache does not support BAN requests');
        }

        $headers = array($this->getTagsHeader() => '('.implode('|', $tags).')(,.+)?$');
        $this->cache->ban($headers);

        return $this;
    }

    /**
     * Send all invalidation requests
     *
     */
    public function flush()
    {
        $this->cache->flush();
    }
}