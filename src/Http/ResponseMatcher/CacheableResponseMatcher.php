<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Http\ResponseMatcher;

use Symfony\Component\HttpFoundation\Response;

/**
 * Matches status codes as defined by IETF RFC 7231.
 *
 * @see https://tools.ietf.org/html/rfc7231#section-6.1
 */
class CacheableResponseMatcher implements ResponseMatcherInterface
{
    private $cacheableStatusCodes = [
        200, 203, 204, 206,
        300, 301,
        404, 405, 410, 414,
        501,
    ];

    public function __construct(array $additionalStatusCodes = [])
    {
        $this->cacheableStatusCodes = array_merge(
            $this->cacheableStatusCodes,
            $additionalStatusCodes
        );
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Response $response)
    {
        return in_array($response->getStatusCode(), $this->cacheableStatusCodes);
    }
}
