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

use FOS\HttpCacheBundle\Http\ResponseMatcher\ExpressionResponseMatcher;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Response;

class ExpressionResponseMatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExpressionMatch()
    {
        $matcher = new ExpressionResponseMatcher('response.getStatusCode() === 200');

        $response = new Response('', 200);
        $this->assertTrue($matcher->matches($response));

        $response = new Response('', 400);
        $this->assertFalse($matcher->matches($response));
    }

    public function testCustomExpressionLanguage()
    {
        $response = new Response('', 500);
        $el = \Mockery::mock(ExpressionLanguage::class)
            ->shouldReceive('evaluate')
                ->once()
                ->with('foo === 200', ['response' => $response])->andReturnTrue()
            ->getMock()
        ;
        $matcher = new ExpressionResponseMatcher('foo === 200', $el);

        $this->assertTrue($matcher->matches($response));
    }
}
