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
     * @param string $url
     *
     * @return $this
     */
    public function refresh($url);
} 