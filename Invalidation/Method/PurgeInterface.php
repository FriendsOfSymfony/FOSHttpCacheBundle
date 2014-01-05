<?php

namespace FOS\HttpCacheBundle\Invalidation\Method;

use FOS\HttpCacheBundle\Invalidation\CacheProxyInterface;

/**
 * An HTTP cache that supports invalidation by purging, that is, removing one
 * URL from the cache
 *
 */
interface PurgeInterface extends CacheProxyInterface
{
    /**
     * Purge a URL
     *
     * Purging a URL will remove the cache for the URL, including the query
     * string, with all its Vary variants.
     *
     * @param string $url
     *
     * @return $this
     */
    public function purge($url);
} 