<?php

namespace FOS\HttpCacheBundle\Invalidation\Method;

/**
 * An HTTP cache that supports invalidation by refresh requests that force a
 * cache miss for one specific URL
 *
 */
interface RefreshInterface
{
    /**
     * Refresh a URL
     *
     * Refreshing a URL will generate a new cached response for the URL,
     * including the query string but excluding any Vary variants.
     *
     * @param string $url
     *
     * @return $this
     */
    public function refresh($url);
} 