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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use FOS\HttpCacheBundle\CacheManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $proxyClient;

    public function setUp()
    {
        $this->proxyClient = \Mockery::mock(ProxyClientInterface::class);
    }

    public function testInvalidateRoute()
    {
        $httpCache = \Mockery::mock(PurgeInterface::class)
            ->shouldReceive('purge')->once()->with('/my/route', array())
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123', array())
            ->shouldReceive('purge')->once()->with('/route/with/params/id/123', array('X-Foo' => 'bar'))
            ->shouldReceive('flush')->once()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->shouldReceive('generate')
            ->with('my_route', array(), UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', array('id' => 123), UrlGeneratorInterface::ABSOLUTE_PATH)
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
        $httpCache = \Mockery::mock(RefreshInterface::class)
            ->shouldReceive('refresh')->once()->with('/my/route', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', null)
            ->shouldReceive('refresh')->once()->with('/route/with/params/id/123', array('X-Foo' => 'bar'))
            ->shouldReceive('flush')->never()
            ->getMock();

        $router = \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->shouldReceive('generate')
            ->with('my_route', array(), UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/my/route')

            ->shouldReceive('generate')
            ->with('route_with_params', array('id' => 123), UrlGeneratorInterface::ABSOLUTE_PATH)
            ->andReturn('/route/with/params/id/123')
            ->getMock();

        $cacheManager = new CacheManager($httpCache, $router);

        $cacheManager
            ->refreshRoute('my_route')
            ->refreshRoute('route_with_params', array('id' => 123))
            ->refreshRoute('route_with_params', array('id' => 123), array('X-Foo' => 'bar'))
        ;
    }

    /**
     * @group legacy
     */
    public function testTagResponse()
    {
        $this->markTestSkipped('TODO refactor to use tag handler');
        $ban = \Mockery::mock(BanInterface::class);
        $router = \Mockery::mock(UrlGeneratorInterface::class);

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
