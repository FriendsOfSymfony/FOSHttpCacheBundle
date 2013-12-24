<?php

namespace FOS\HttpCacheBundle\HttpCache\Invalidation;

/**
 * An HTTP cache that supports invalidation by banning, that is, removing
 * objects from the cache that match a regular expression
 *
 */
interface BanInterface
{
    const REGEX_MATCH_ALL = '.*';
    const CONTENT_TYPE_ALL = self::REGEX_MATCH_ALL;

    /**
     * Ban paths matching a regular expression
     *
     * @param string $path        Path that will be banned. This can be a regex,
     *                            for instance, "\.png$"
     * @param string $contentType Content-type that cached responses must
     *                            match in order to be invalidated
     * @param array  $hosts       Host that cached responses must match in
     *                            order to be invalidated
     *
     * @return $this
     */
    public function ban($path, $contentType, array $hosts = null);
}