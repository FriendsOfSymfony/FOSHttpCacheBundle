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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This matcher wraps a Symfony2 RequestMatcher and adds some criteria for the
 * response.
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
     * @var array
     */
    private $criteria;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @param RequestMatcherInterface $requestMatcher Strategy to match the request.
     * @param array                   $criteria       Additional criteria not covered by request matcher.
     */
    public function __construct(RequestMatcherInterface $requestMatcher, array $criteria)
    {
        $this->requestMatcher = $requestMatcher;
        $this->criteria = $criteria;
    }

    /**
     * {@inheritDoc}
     */
    public function matches(Request $request, Response $response)
    {
        if (!$this->requestMatcher->matches($request)) {
            return false;
        }

        if (!empty($this->criteria['match_response'])) {
            if (!$this->getExpressionLanguage()->evaluate($this->criteria['match_response'], array(
                'response' => $response,
            ))) {
                return false;
            }
        } else {
            /* We can't use Response::isCacheable because that also checks if cache
             * headers are already set. As we are about to set them, that would
             * always return false.
             */
            $status = array(200, 203, 204, 300, 301, 302, 404, 410);
            if (!empty($this->criteria['additional_cacheable_status'])) {
                $status = array_merge($status, $this->criteria['additional_cacheable_status']);
            }
            if (!in_array($response->getStatusCode(), $status)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ExpressionLanguage
     */
    private function getExpressionLanguage()
    {
        if (!$this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }
}
