<?php

namespace FOS\HttpCacheBundle\Tests;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Invalidation\Method\BanInterface;
use \Mockery;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $router;
    protected $cacheManager;

    public function setUp()
    {
        $this->router = \Mockery::mock('\Symfony\Component\Routing\Router[generate]');
        $this->cacheProxy = \Mockery::mock('\FOS\HttpCacheBundle\Invalidation\CacheProxyInterface');
    }

    public function testDuplicatePaths()
    {
        $this->router
            ->shouldReceive('generate')
            ->with('same_route', array())
            ->twice()
            ->andReturn('/same/route')
            ->getMock();

        $cacheManager = new CacheManager(
            $this->cacheProxy,
            $this->router
        );

        $cacheManager->invalidateRoute('same_route');
        $cacheManager->invalidateRoute('same_route');

        $this->assertCount(1, $cacheManager->getInvalidationQueue());
    }

    public function testInvalidateRoute()
    {
        return;
        $httpCache = \Mockery::mock('\FOS\HttpCacheBundle\HttpCache\HttpCacheInterface')
            ->shouldReceive('invalidateUrls')
            ->with(array('/my/route', '/route/with/params/id/123'))
            ->once()
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

    public function testInvalidateTags()
    {
        $ban = \Mockery::mock(
            '\FOS\HttpCacheBundle\Invalidation\Method\BanInterface,'
            . ' \FOS\HttpCacheBundle\Invalidation\CacheProxyInterface'
        )
            ->shouldReceive('ban')
            ->with(array('X-Cache-Tags' => '(post-1|posts)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheManager = new CacheManager($ban, $this->router);
        $cacheManager->invalidateTags(array('post-1', 'posts'));
    }
}