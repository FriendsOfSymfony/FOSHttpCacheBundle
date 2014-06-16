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

class RequestMatcher implements RequestMatcherInterface
{
    private $method;

    private $accept;

    public function __construct($accept = 'application/vnd.fos.user-context-hash', $method = null)
    {
        $this->accept = $accept;
        $this->method = $method;
    }

    /**
     * {@inheritDoc}
     */
    public function matches(Request $request)
    {
        if ($this->accept !== null && $this->accept != $request->headers->get('accept', null)) {
            return false;
        }

        if ($this->method !== null && $this->method != $request->getMethod()) {
            return false;
        }

        return true;
    }
}
