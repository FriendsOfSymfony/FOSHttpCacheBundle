<?php

namespace FOS\HttpCacheBundle;

use FOS\HttpCacheBundle\HttpCache\HttpCacheInterface;
use FOS\HttpCacheBundle\Invalidation\CacheProxyInterface;
use FOS\HttpCacheBundle\Invalidation\Method\BanInterface;
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
     * Invalidate a path (URL)
     *
     * @param string $path Path
     *
     * @return $this
     */
    public function invalidatePath($path, array $headers = array())
    {
        $this->invalidationQueue[$path] = $headers;

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

    public function refreshPath($path, $headers)
    {
        $headers = array("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");

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

    /**
     * Get paths (URLs) that are queued for invalidation
     *
     * @return array
     */
    public function getInvalidationQueue()
    {
        return \array_values($this->invalidationQueue);
    }
}