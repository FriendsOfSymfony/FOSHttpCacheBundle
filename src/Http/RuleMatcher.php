<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Http;

use FOS\HttpCacheBundle\Http\ResponseMatcher\ResponseMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Combines a RequestMatcherInterface and a ResponseMatcherInterface.
 *
 * Both must match for the RuleMatcher to match.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class RuleMatcher implements RuleMatcherInterface
{
    /**
     * @var RequestMatcherInterface
     */
    private $requestMatcher;

    /**
     * @var ResponseMatcherInterface
     */
    private $responseMatcher;

    /**
     * @param RequestMatcherInterface  $requestMatcher|null  Request matcher
     * @param ResponseMatcherInterface $responseMatcher|null Response matcher
     */
    public function __construct(
        RequestMatcherInterface $requestMatcher = null,
        ResponseMatcherInterface $responseMatcher = null
    ) {
        $this->requestMatcher = $requestMatcher;
        $this->responseMatcher = $responseMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Request $request, Response $response)
    {
        if ($this->requestMatcher && !$this->requestMatcher->matches($request)) {
            return false;
        }

        if ($this->responseMatcher && !$this->responseMatcher->matches($response)) {
            return false;
        }

        return true;
    }
}
