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

use FOS\HttpCacheBundle\UserContext\RequestMatcher;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestMatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMatch()
    {
        $requestMatcher = new RequestMatcher('application/vnd.test', 'HEAD');

        $request = new Request();
        $request->headers->set('accept', 'application/vnd.test');
        $request->setMethod('HEAD');

        $this->assertTrue($requestMatcher->matches($request));

        $requestMatcher = new RequestMatcher('application/vnd.test');

        $this->assertTrue($requestMatcher->matches($request));

        $request->setMethod('GET');

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testNoMatch()
    {
        $requestMatcher = new RequestMatcher('application/vnd.test', 'HEAD');

        $request = new Request();
        $request->setMethod('GET');

        $this->assertFalse($requestMatcher->matches($request));

        $request->headers->set('accept', 'application/vnd.test');

        $this->assertFalse($requestMatcher->matches($request));
    }
}
