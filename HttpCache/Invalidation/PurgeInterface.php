<?php

namespace FOS\HttpCacheBundle\HttpCache\Invalidation;

/**
 * An HTTP cache that supports invalidation by purging, that is, removing one
 * URL from the cache
 *
 */
interface PurgeInterface
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