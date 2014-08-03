<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Test;

use FOS\HttpCacheBundle\Test\ProxyTestCase;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\WebTestCase;

class ProxyTestCaseTest extends ProxyTestCase
{
    protected function setUp()
    {
        static::getContainer()->mock(
            'fos_http_cache.test.default_proxy_server',
            '\FOS\HttpCache\Test\Proxy\VarnishProxy'
        );

        parent::setUp();
    }

    public function testGetProxyClient()
    {
        $this->assertInstanceOf(
            '\FOS\HttpCache\ProxyClient\ProxyClientInterface',
            $this->getProxyClient()
        );
    }

    public function testAssertHit()
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('X-Cache')->once()->andReturn(true)
            ->shouldReceive('getHeader')->with('X-Cache')->once()->andReturn('HIT')
            ->getMock();

        $this->assertHit($response);
    }

    public function testAssertMiss()
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('X-Cache')->once()->andReturn(true)
            ->shouldReceive('getHeader')->with('X-Cache')->once()->andReturn('MISS')
            ->getMock();

        $this->assertMiss($response);
    }

    protected function getResponseMock()
    {
        return \Mockery::mock(
            '\Guzzle\Http\Message\Response[hasHeader,getHeader]',
            array(null)
        );
    }
}
