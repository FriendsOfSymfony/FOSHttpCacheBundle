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
     * @param string $url
     *
     * @return $this
     */
    public function purge($url);
} 