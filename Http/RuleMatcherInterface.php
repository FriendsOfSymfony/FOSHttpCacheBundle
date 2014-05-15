<?php

namespace FOS\HttpCacheBundle\Http;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * A matcher similar to the Symfony2 RequestMatcher but also considering the
 * response to decide if a rule should apply to this response.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
interface RuleMatcherInterface
{
    /**
     * Check whether the request and response both match.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool True if request and response match, false otherwise.
     */
    public function matches(Request $request, Response $response);
}
