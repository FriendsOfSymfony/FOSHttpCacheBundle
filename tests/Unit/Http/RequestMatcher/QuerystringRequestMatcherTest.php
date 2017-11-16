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

use FOS\HttpCacheBundle\Http\RequestMatcher\QuerystringRequestMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class QuerystringRequestMatcherTest extends TestCase
{
    public function testMatchesReturnsFalseIfParentCallFails()
    {
        $requestMatcher = new QuerystringRequestMatcher('/foo');
        $request = Request::create('http://localhost/bar?token=myvalue');

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testMatchesReturnsTrueWhenNoQueryStringIsSet()
    {
        $requestMatcher = new QuerystringRequestMatcher();
        $request = Request::create('http://localhost/bar?token=myvalue');

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testMatchesReturnsTrueIfQueryStringMatches()
    {
        $requestMatcher = new QuerystringRequestMatcher();
        $requestMatcher->setQueryString('(^|&)token=hello!(&|$)');
        $request = Request::create('http://localhost/bar?token=hello%21');

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testMatchesReturnsFalseIfQueryStringDoesntMatch()
    {
        $requestMatcher = new QuerystringRequestMatcher();
        $requestMatcher->setQueryString('(^|&)mytoken=');
        $request = Request::create('http://localhost/bar?token=myvalue');

        $this->assertFalse($requestMatcher->matches($request));
    }
}
