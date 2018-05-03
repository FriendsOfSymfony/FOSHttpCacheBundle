<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\Http\ResponseMatcher;

use FOS\HttpCacheBundle\Http\ResponseMatcher\NonErrorResponseMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class NonErrorResponseMatcherTest extends TestCase
{
    public function testSuccess()
    {
        $matcher = new NonErrorResponseMatcher();

        $response = new Response('', 200);
        $this->assertTrue($matcher->matches($response));

        $response = new Response('', 399);
        $this->assertTrue($matcher->matches($response));
    }

    public function testError()
    {
        $matcher = new NonErrorResponseMatcher();

        $response = new Response('', 199);
        $this->assertFalse($matcher->matches($response));

        $response = new Response('', 400);
        $this->assertFalse($matcher->matches($response));
    }
}
