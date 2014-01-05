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

    public function testInvalidateRoute()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCacheBundle\Invalidation\Method\PurgeInterface')
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

    public function testInvalidateTags()
    {
        $ban = \Mockery::mock('\FOS\HttpCacheBundle\Invalidation\Method\BanInterface')
            ->shouldReceive('ban')
            ->with(array('X-Cache-Tags' => '(post-1|posts)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheManager = new CacheManager($ban, $this->router);
        $cacheManager->invalidateTags(array('post-1', 'posts'));
    }
}