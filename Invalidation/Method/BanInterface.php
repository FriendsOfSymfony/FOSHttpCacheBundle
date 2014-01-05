<?php

namespace FOS\HttpCacheBundle\Invalidation\Method;

use FOS\HttpCacheBundle\Invalidation\CacheProxyInterface;

/**
 * An HTTP cache that supports invalidation by banning, that is, removing
 * objects from the cache that match a regular expression
 *
 */
interface BanInterface extends CacheProxyInterface
{
    const REGEX_MATCH_ALL = '.*';
    const CONTENT_TYPE_ALL = self::REGEX_MATCH_ALL;

    /**
     * Ban cached objects matching HTTP headers
     *
     * Please make sure to configure your HTTP caching proxy to set the headers
     * supplied here on the cached objects. So if you want to match objects by
     * host name, configure your proxy to copy the host to a custom HTTP header
     * such as X-Host.
     *
     * @param array $headers HTTP headers that path must match to be banned.
     *                       Each header is either a:
     *                       - regular string ('X-Host' => 'example.com')
     *                       - or a POSIX regular expression
     *                         ('X-Host' => '^(www\.)?(this|that)\.com$').
     *
     * @return $this
     */
    public function ban(array $headers);

    /**
     * Ban paths matching a regular expression
     *
     * @param string $path        Path that will be banned. This can be a regex,
     *                            for instance, "\.png$"
     * @param string $contentType Content-type that cached responses must
     *                            match in order to be invalidated (optional).
     *                            This can be part of a content type or regex,
     *                            for instance, "text"
     * @param array  $hosts       Hosts that cached responses must match in
     *                            order to be invalidated (optional). This can
     *                            be:
     *                            - a regex, e.g. "^(www\.)?(this|that)\.com$"
     *                            - an array of hosts that will be banned
     *                            - null: to ban the default host.
     *
     * @return $this
     */
    public function banPath($path, $contentType = self::CONTENT_TYPE_ALL, array $hosts = null);
}