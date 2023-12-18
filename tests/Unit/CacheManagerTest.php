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

use FOS\HttpCache\ProxyClient\HttpProxyClient;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\ProxyClient;
use FOS\HttpCacheBundle\CacheManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

class CacheManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $proxyClient;

    public function setUp(): void
    {
        $this->proxyClient = \Mockery::mock(ProxyClient::class);
    }

    public function testInvalidateRoute()
    {
        $httpCache = \Mockery::mock(PurgeCapable::class)
            ->shouldReceive('purge')->once()->with('/my/route', [])
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123', [])
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123', ['X-Foo' => 'bar'])
            ->shouldReceive('flush')->once()
            ->getMock();

        $router = \Mockery::mock(UrlGeneratorInterface::class)
            ->shouldReceive('generate')
            ->with('my_route', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', ['id' => 123], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/route/with/params/id/123')
            ->getMock();

        $cacheManager = new CacheManager($httpCache, $router);

        $cacheManager->invalidateRoute('my_route')
            ->invalidateRoute('route_with_params', ['id' => 123])
            ->invalidateRoute('route_with_params', ['id' => 123], ['X-Foo' => 'bar'])
            ->flush();
    }

    public function testRefreshRoute()
    {
        $httpCache = \Mockery::mock(RefreshCapable::class)
            ->shouldReceive('refresh')->once()->with('/my/route', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', ['X-Foo' => 'bar'])
            ->shouldReceive('flush')->never()
            ->getMock();

        $router = \Mockery::mock(UrlGeneratorInterface::class)
            ->shouldReceive('generate')
            ->with('my_route', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', ['id' => 123], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/route/with/params/id/123')
            ->getMock();

        $cacheManager = new CacheManager($httpCache, $router);

        $cacheManager
            ->refreshRoute('my_route')
            ->refreshRoute('route_with_params', ['id' => 123])
            ->refreshRoute('route_with_params', ['id' => 123], ['X-Foo' => 'bar'])
        ;
    }

    public function testSkipFlushOnEmptyInvalidationsAndLazyLoaded() {
        $proxyClient = \Mockery::mock(HttpProxyClient::class, LazyObjectInterface::class)
            ->shouldNotReceive('flush')
            ->shouldReceive('isLazyObjectInitialized')->andReturn(false)
            ->getMock();

        $router = \Mockery::mock(UrlGeneratorInterface::class);

        $cacheInvalidator = new CacheManager($proxyClient, $router);
        $this->assertEquals(0, $cacheInvalidator->flush());
    }

    public function testFlushOnNotLazyLoaded() {
        $proxyClient = \Mockery::mock(HttpProxyClient::class)
            ->shouldReceive('flush')->andReturn(0)
            ->shouldNotReceive('isLazyObjectInitialized')
            ->getMock();

        $router = \Mockery::mock(UrlGeneratorInterface::class);

        $cacheInvalidator = new CacheManager($proxyClient, $router);
        $this->assertEquals(0, $cacheInvalidator->flush());
    }

    public function testFlushOnLazyLoaded() {
        $proxyClient = \Mockery::mock(HttpProxyClient::class, LazyObjectInterface::class, PurgeCapable::class);
        $proxyClient->shouldReceive('flush')->andReturn(1);
        $proxyClient->shouldReceive('purge');
        $proxyClient->shouldReceive('isLazyObjectInitialized')->andReturn(true);

        $router = \Mockery::mock(UrlGeneratorInterface::class);

        $cacheInvalidator = new CacheManager($proxyClient, $router);
        $cacheInvalidator->invalidatePath('/foo');
        $this->assertEquals(1, $cacheInvalidator->flush());
    }
}
