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
 * Additional request matcher for query string matching.
 */
class QueryStringRequestMatcher implements RequestMatcherInterface
{
    /**
     * @var string Regular expression to match the query string part of the request url
     */
    private string $queryString;

    public function __construct(string $queryString)
    {
        $this->queryString = $queryString;
    }

    public function matches(Request $request): bool
    {
        return (bool) preg_match('{'.$this->queryString.'}', rawurldecode($request->getQueryString() ?: ''));
    }
}
