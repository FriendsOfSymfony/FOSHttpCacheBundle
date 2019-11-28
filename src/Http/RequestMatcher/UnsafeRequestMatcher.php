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
use Symfony\Component\HttpKernel\Kernel;

class UnsafeRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request)
    {
        // hack needed for compatibility with SF 3.4 => 4.3
        // sf 3.4 => isMethodSafe(false)
        // sf 4.3 => isMethodSafe() or isMethodSafe(false)
        // sf 4.4 => isMethodSafe()
        if (Kernel::VERSION_ID >= 40300) {
            return !$request->isMethodSafe();
        } else {
            return !$request->isMethodSafe(false);
        }
    }
}
