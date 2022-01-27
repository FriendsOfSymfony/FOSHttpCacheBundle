<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Http\RequestMatcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * @see https://tools.ietf.org/html/rfc7231#section-4.2.3
 */
class CacheableRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    {
        return $request->isMethodCacheable();
    }
}
