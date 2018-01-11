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

    private $sessionNamePrefix;

    /**
     * @param array  $userIdentifierHeaders List of request headers that authenticate a non-anonymous request
     * @param string $sessionNamePrefix     Prefix for session cookies. Must match your PHP session configuration
     */
    public function __construct(array $userIdentifierHeaders, $sessionNamePrefix)
    {
        $this->userIdentifierHeaders = $userIdentifierHeaders;
        $this->sessionNamePrefix = $sessionNamePrefix;
    }

    public function matches(Request $request)
    {
        foreach ($this->userIdentifierHeaders as $header) {
            if ($request->headers->has($header)) {
                if ('cookie' === strtolower($header)) {
                    foreach ($request->cookies as $name => $value) {
                        if (0 === strpos($name, $this->sessionNamePrefix)) {
                            return false;
                        }
                    }
                } else {
                    return false;
                }
            }
        }

        return true;
    }
}
