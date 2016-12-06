<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\UserContext;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Matches anonymous requests using a list of identification headers.
 */
class AnonymousRequestMatcher implements RequestMatcherInterface
{
    private $userIdentifierHeaders;

    /**
     * @param array $userIdentifierHeaders List of request headers that authenticate a non-anonymous request
     */
    public function __construct(array $userIdentifierHeaders)
    {
        $this->userIdentifierHeaders = $userIdentifierHeaders;
    }

    public function matches(Request $request)
    {
        foreach ($this->userIdentifierHeaders as $header) {
            if ($request->headers->has($header)) {
                if (strtolower($header) === 'cookie' && 0 === $request->cookies->count()) {
                    // ignore empty cookie header
                    continue;
                }

                return false;
            }
        }

        return true;
    }
}
