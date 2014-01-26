<?php

namespace FOS\HttpCacheBundle\Tests;

use FOS\HttpCacheBundle\CacheManager;
use \Mockery;
use Symfony\Component\HttpFoundation\Response;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $router;
    protected $cacheManager;

    public function setUp()
    {
        $this->router = \Mockery::mock('\Symfony\Component\Routing\Router[generate]');
        $this->cacheProxy = \Mockery::mock('\FOS\HttpCache\Invalidation\CacheProxyInterface');
    }

    public function testInvalidateRoute()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\PurgeInterface')
            ->shouldReceive('purge')->once()->with('/my/route')
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123')
            ->shouldReceive('flush')->once()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Router[generate]')
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
            ->flush();
    }

    public function testRefreshRoute()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\RefreshInterface')
            ->shouldReceive('refresh')->once()->with('/my/route', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', null)
            ->shouldReceive('flush')->never()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Router[generate]')
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
        ;
    }

    public function testTagResponse()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface');
        $tags1 = array('post-1', 'posts');
        $tags2 = array('post-2');
        $tags3 = array('different');

        $cacheManager = new CacheManager($ban, $this->router);
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
