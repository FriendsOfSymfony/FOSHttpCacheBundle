<?php

namespace FOS\HttpCacheBundle\Tests;

use FOS\HttpCacheBundle\CacheManager;
use \Mockery;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testDuplicatePaths()
    {
        $router = \Mockery::mock('\Symfony\Component\Routing\Router[generate]')
            ->shouldReceive('generate')
            ->with('same_route', array())
            ->twice()
            ->andReturn('/same/route')
            ->getMock();

        $cacheManager = new CacheManager(
            \Mockery::mock('\FOS\HttpCacheBundle\HttpCache\HttpCacheInterface'),
            $router
        );

        $cacheManager->invalidateRoute('same_route');
        $cacheManager->invalidateRoute('same_route');

        $this->assertCount(1, $cacheManager->getInvalidationQueue());
    }

    public function testInvalidateRoute()
    {
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
}