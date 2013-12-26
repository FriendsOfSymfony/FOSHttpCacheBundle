<?php

namespace FOS\HttpCacheBundle\HttpCache\Invalidation;

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
     * Refreshing a URL will generate a new cached response for it.
     *
     * @param string $url
     *
     * @return $this
     */
    public function refresh($url);
} 