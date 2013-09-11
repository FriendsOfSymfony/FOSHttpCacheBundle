<?php

namespace Driebit\HttpCacheBundle\Tests;

use Driebit\HttpCacheBundle\CacheManager;
use \Mockery;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidateRoute()
    {
        $httpCache = \Mockery::mock('\Driebit\HttpCacheBundle\HttpCache\HttpCacheInterface')
            ->shouldReceive('invalidateUrls')
            ->with(array('/my/route', '/route/with/params/id/123'))
            ->once()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Router[generate]')
            ->shouldReceive('generate')
            ->with('my_route', array())
            ->once()
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', array('id' => 123))->andReturn('/route/with/params/id/123')
            ->once()
            ->getMock();

        $cacheManager = new CacheManager($httpCache, $router);

        $cacheManager->invalidateRoute('my_route')
            ->invalidateRoute('route_with_params', array('id' => 123))
            ->flush();
    }
}