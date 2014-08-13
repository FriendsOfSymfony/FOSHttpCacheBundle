<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit;

use FOS\HttpCacheBundle\CacheManager;
use \Mockery;
use Symfony\Component\HttpFoundation\Response;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $proxyClient;

    public function setUp()
    {
        $this->proxyClient = \Mockery::mock('\FOS\HttpCache\ProxyClient\ProxyClientInterface');
    }

    public function testInvalidateRoute()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface')
            ->shouldReceive('purge')->once()->with('/my/route', array())
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123', array())
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123', array('X-Foo' => 'bar'))
            ->shouldReceive('flush')->once()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->shouldReceive('generate')
            ->with('my_route', array())
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', array('id' => 123))
            ->andReturn('/route/with/params/id/123')
            ->getMock();

        $cacheManager = new CacheManager($httpCache, $router);

        $cacheManager->invalidateRoute('my_route')
            ->invalidateRoute('route_with_params', array('id' => 123))
            ->invalidateRoute('route_with_params', array('id' => 123), array('X-Foo' => 'bar'))
            ->flush();
    }

    public function testRefreshRoute()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface')
            ->shouldReceive('refresh')->once()->with('/my/route', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', array('X-Foo' => 'bar'))
            ->shouldReceive('flush')->never()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->shouldReceive('generate')
            ->with('my_route', array())
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', array('id' => 123))
            ->andReturn('/route/with/params/id/123')
            ->getMock();

        $cacheManager = new CacheManager($httpCache, $router);

        $cacheManager
            ->refreshRoute('my_route')
            ->refreshRoute('route_with_params', array('id' => 123))
            ->refreshRoute('route_with_params', array('id' => 123), array('X-Foo' => 'bar'))
        ;
    }

    public function testTagResponse()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\BanInterface');
        $router = \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $tags1 = array('post-1', 'posts');
        $tags2 = array('post-2');
        $tags3 = array('different');

        $cacheManager = new CacheManager($ban, $router);
        $response = new Response();
        $response->headers->set($cacheManager->getTagsHeader(), '');
        $cacheManager->tagResponse($response, $tags1);
        $this->assertTrue($response->headers->has($cacheManager->getTagsHeader()));
        $this->assertEquals(implode(',', $tags1), $response->headers->get($cacheManager->getTagsHeader()));

        $cacheManager->tagResponse($response, $tags2);
        $this->assertEquals(implode(',', array_merge($tags1, $tags2)), $response->headers->get($cacheManager->getTagsHeader()));

        $cacheManager->tagResponse($response, $tags3, true);
        $this->assertEquals(implode(',', $tags3), $response->headers->get($cacheManager->getTagsHeader()));
    }
}
