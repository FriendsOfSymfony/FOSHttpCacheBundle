<?php

namespace Driebit\HttpCacheBundle\HttpCache;

/**
 * A shared HTTP cache
 *
 * @author David de Boer <david@driebit.nl>
 */
interface HttpCacheInterface
{
    /**
     * Invalidate one URL
     *
     * @param string $url
     */
    public function invalidateUrl($url);

    /**
     * Invalidate multiple URLs
     *
     * @param array $urls
     */
    public function invalidateUrls(array $urls);
}