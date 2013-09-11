<?php

namespace Driebit\HttpCacheBundle\Tests\EventListener;

use Driebit\HttpCacheBundle\EventListener\InvalidationListener;
use Driebit\HttpCacheBundle\Invalidator\Invalidator;
use Driebit\HttpCacheBundle\Invalidator\InvalidatorCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use \Mockery;
use Driebit\HttpCacheBundle\HttpCache\Varnish;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class InvalidationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoRoutesInvalidedWhenResponseIsUnsuccessful()
    {
        $cacheManager = \Mockery::mock('\Driebit\HttpCacheBundle\CacheManager')
            ->shouldDeferMissing()
            ->shouldReceive('invalidateRoute')
            ->never()
            ->getMock();

        $invalidators = \Mockery::mock('\Driebit\HttpCacheBundle\Invalidator\InvalidatorCollection')
            ->shouldReceive('hasInvalidatorRoute')
            ->with('my_route')
            ->andReturn(false)
            ->getMock();

        $listener = new InvalidationListener(
            $cacheManager,
            $invalidators,
            \Mockery::mock('\Symfony\Component\Routing\RouterInterface')
        );

        $request = new Request();
        $request->attributes->set('_route', 'my_route');

        $event = $this->getEvent($request, new Response('', 500));
        $listener->onKernelTerminate($event);
    }


    public function testOnKernelTerminate()
    {
        $cacheManager = \Mockery::mock('\Driebit\HttpCacheBundle\CacheManager')
            ->shouldReceive('invalidateRoute')
            ->with('route_invalidated', array('id' => '123'))
            ->shouldReceive('invalidateRoute')->with('route_invalidated_special', array('id' => '123', 'special' => 'bla'))
            ->shouldReceive('flush')->once()
            ->getMock();

        $routes = new RouteCollection();
        $route = new Route('/edit/something/{id}/{special}');
        $route2 = new Route('/retrieve/something/{id}');
        $route3 = new Route('/retrieve/something/{id}/{special}');
        $routes->add('route_invalidator', $route);
        $routes->add('route_invalidated', $route2);
        $routes->add('route_invalidated_special', $route3);

        $router = \Mockery::mock('\Symfony\Component\Routing\Router')
            ->shouldDeferMissing()
            ->shouldReceive('getRouteCollection')
            ->andReturn($routes)
            ->getMock();

        $invalidator = new Invalidator();
        $invalidator->addInvalidatorRoute('route_invalidator');
        $invalidator->addInvalidatedRoute('route_invalidated');
        $invalidator->addInvalidatedRoute('route_invalidated_special');

        $invalidators = new InvalidatorCollection();
        $invalidators->addInvalidator($invalidator);

        $listener = new InvalidationListener($cacheManager, $invalidators, $router);

        $request = new Request();
        $request->attributes->set('_route', 'route_invalidator');
        $request->attributes->set('_route_params', array('id' => '123', 'special' => 'bla'));

        $event = $this->getEvent($request);
        $listener->onKernelTerminate($event);
    }

    protected function getEvent(Request $request, Response $response = null)
    {
        return new PostResponseEvent(
            \Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            null !== $response ? $response : new Response()
        );
    }
}