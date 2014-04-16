<?php

namespace FOS\HttpCacheBundle;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * The CacheManager is a CacheInvalidator but adds symfony Route support and
 * response tagging to the framework agnostic FOS\HttpCache\CacheInvalidator.
 *
 * @author David de Boer <david@driebit.nl>
 */
class CacheManager extends CacheInvalidator
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Constructor
     *
     * @param ProxyClientInterface $cache  HTTP cache
     * @param RouterInterface      $router Symfony router
     */
    public function __construct(ProxyClientInterface $cache, RouterInterface $router)
    {
        parent::__construct($cache);
        $this->router = $router;
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
        if (!$replace && $response->headers->has($this->getTagsHeader())) {
            $header = $response->headers->get($this->getTagsHeader());
            if ('' !== $header) {
                $tags = array_merge(
                    explode(',', $response->headers->get($this->getTagsHeader())),
                    $tags
                );
            }
        }

        $uniqueTags = array_unique($tags);
        $response->headers->set($this->getTagsHeader(), implode(',', $uniqueTags));

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
    public function invalidateRoute($name, array $parameters = array())
    {
        $this->invalidatePath($this->router->generate($name, $parameters));

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
}
