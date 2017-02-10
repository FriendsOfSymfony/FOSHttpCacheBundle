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
 * "A cache MUST invalidate the effective Request URI ... when a non-error
 * status code is received in response to an unsafe request method".
 *
 * @see https://tools.ietf.org/html/rfc7234#section-4.4
 */
class NonErrorResponseMatcher implements ResponseMatcherInterface
{
    public function matches(Response $response)
    {
        return $response->getStatusCode() >= 200
            && $response->getStatusCode() < 400;
    }
}
