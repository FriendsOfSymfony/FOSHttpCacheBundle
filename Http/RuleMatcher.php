<?php

namespace FOS\HttpCacheBundle\Http;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * This matcher wraps a Symfony2 RequestMatcher and adds some criteria for the
 * response and on the security context.
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
     * @param RequestMatcherInterface  $requestMatcher  Strategy to match the request.
     * @param array                    $criteria        Additional criteria not covered by request matcher.
     * @param SecurityContextInterface $securityContext Used to handle unless_role criteria. (optional)
     */
    public function __construct(RequestMatcherInterface $requestMatcher, array $criteria, SecurityContextInterface $securityContext = null)
    {
        $this->requestMatcher = $requestMatcher;
        $this->criteria = $criteria;
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritDoc}
     */
    public function matches(Request $request, Response $response)
    {
        if (!empty($this->criteria['unless_role'])
            && $this->securityContext
            && $this->securityContext->isGranted($this->criteria['unless_role'])
        ) {
            return false;
        }

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
            $status = array(200, 203, 300, 301, 302, 404, 410);
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
