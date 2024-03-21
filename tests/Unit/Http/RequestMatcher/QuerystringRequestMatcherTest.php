<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\Http\RequestMatcher;

use FOS\HttpCacheBundle\Http\RequestMatcher\QueryStringRequestMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class QuerystringRequestMatcherTest extends TestCase
{
    public function testMatchesReturnsFalseIfParentCallFails(): void
    {
        $requestMatcher = new QueryStringRequestMatcher('/foo');
        $request = Request::create('http://localhost/bar?token=myvalue');

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testMatchesReturnsTrueIfQueryStringMatches(): void
    {
        $requestMatcher = new QueryStringRequestMatcher('(^|&)token=hello!(&|$)');
        $request = Request::create('http://localhost/bar?token=hello%21');

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testMatchesReturnsFalseIfQueryStringDoesntMatch(): void
    {
        $requestMatcher = new QueryStringRequestMatcher('(^|&)mytoken=');
        $request = Request::create('http://localhost/bar?token=myvalue');

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testMatchesReturnsFalseIfQueryStringIsEmpty(): void
    {
        $requestMatcher = new QueryStringRequestMatcher('(^|&)mytoken=');
        $request = Request::create('http://localhost/bar');

        $this->assertFalse($requestMatcher->matches($request));
    }
}
