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
use Symfony\Component\HttpFoundation\RequestMatcher as SymfonyRequestMatcher;

/**
 * Extend the Symfony RequestMatcher class to support query string matching.
 */
class QuerystringRequestMatcher extends SymfonyRequestMatcher
{
    /**
     * @var string Regular expression to match the query string part of the request url
     */
    private $queryString;

    /**
     * @param string $queryString
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Request $request): bool
    {
        if (!parent::matches($request)) {
            return false;
        }

        if (null === $this->queryString) {
            return true;
        }

        return (bool) preg_match('{'.$this->queryString.'}', rawurldecode($request->getQueryString() ?: ''));
    }
}
