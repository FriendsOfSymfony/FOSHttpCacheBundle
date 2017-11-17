<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\Http;

use FOS\HttpCacheBundle\Http\ResponseMatcher\CacheableResponseMatcher;
use FOS\HttpCacheBundle\Http\RuleMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;

class RuleMatcherTest extends TestCase
{
    public function testRequestMatcherCalled()
    {
        $requestMatcher = new RequestMatcher(null, null, null, null, ['_controller' => '^AcmeBundle:Default:index$']);
        $ruleMatcher = new RuleMatcher($requestMatcher);

        $request = new Request();
        $request->attributes->set('_controller', 'AcmeBundle:Default:index');

        $this->assertTrue($ruleMatcher->matches($request, new Response()));
    }

    public function testAdditionalCacheableStatus()
    {
        $ruleMatcher = new RuleMatcher(new RequestMatcher(), new CacheableResponseMatcher([400, 500]));

        $this->assertFalse($ruleMatcher->matches(new Request(), new Response('', 504)));
        $this->assertTrue($ruleMatcher->matches(new Request(), new Response('', 500)));
        $this->assertTrue($ruleMatcher->matches(new Request(), new Response('', 200)));
    }
}
