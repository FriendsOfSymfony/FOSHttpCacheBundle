<?php

namespace Driebit\HttpCacheBundle;

use Driebit\HttpCacheBundle\HttpCache\HttpCacheInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Manages HTTP cache invalidations
 *
 */
class CacheManager
{
    /**
     * @var HttpCacheInterface
     */
    protected $cache;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Invalidation queue
     *
     * @var array
     */
    protected $invalidationQueue = array();

    /**
     * Constructor
     *
     * @param HttpCacheInterface $cache  HTTP cache
     * @param RouterInterface    $router Symfony router
     */
    public function __construct(HttpCacheInterface $cache, RouterInterface $router)
    {
        $this->cache = $cache;
        $this->router = $router;
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
        $this->invalidationQueue[] = $this->router->generate($name, $parameters);

        return $this;
    }

    /**
     * Flush all paths queued for invalidation
     */
    public function flush()
    {
        if (0 === count($this->invalidationQueue)) {
            return;
        }

        $this->cache->invalidateUrls(\array_unique($this->invalidationQueue));
        $this->invalidationQueue = array();
    }

    /**
     * Get queue of routes to be invalidated
     *
     * @return array
     */
    public function getInvalidationQueue()
    {
        return $this->invalidationQueue;
    }
}