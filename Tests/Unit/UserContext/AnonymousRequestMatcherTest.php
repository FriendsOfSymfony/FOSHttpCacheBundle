<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\UserContext;

use FOS\HttpCacheBundle\UserContext\AnonymousRequestMatcher;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;

class AnonymousRequestMatcherTest extends PHPUnit_Framework_TestCase
{
    public function testMatchAnonymousRequest()
    {
        $request = new Request();

        $requestMatcher = new AnonymousRequestMatcher(['Cookie', 'Authorization']);

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testNoMatchIfCookie()
    {
        $request = new Request();
        $request->headers->set('Cookie', 'PHPSESSID7e476fc9f29f69d2ad6f11dbcd663b42=25f6d9c5a843e3c948cd26902385a527');
        $request->cookies->set('PHPSESSID7e476fc9f29f69d2ad6f11dbcd663b42', '25f6d9c5a843e3c948cd26902385a527');

        $requestMatcher = new AnonymousRequestMatcher(['Cookie', 'Authorization']);

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testNoMatchIfEmptyCookieHeader()
    {
        $request = new Request();
        $request->headers->set('Cookie', '');

        $requestMatcher = new AnonymousRequestMatcher(['Cookie', 'Authorization']);

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testNoMatchIfAuthenticationHeader()
    {
        $request = new Request();
        $request->headers->set('Authorization', 'foo: bar');

        $requestMatcher = new AnonymousRequestMatcher(['Cookie', 'Authorization']);

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testMatchEmptyCookieHeaderHeader()
    {
        $request = new Request();
        $request->headers->set('Cookie', '');

        $requestMatcher = new AnonymousRequestMatcher(['Cookie', 'Authorization']);

        $this->assertTrue($requestMatcher->matches($request));
    }
}
