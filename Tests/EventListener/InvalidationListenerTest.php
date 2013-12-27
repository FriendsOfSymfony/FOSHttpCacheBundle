<?php

namespace FOS\HttpCacheBundle\Tests\EventListener;

use FOS\HttpCacheBundle\EventListener\InvalidationListener;
use FOS\HttpCacheBundle\Invalidator\Invalidator;
use FOS\HttpCacheBundle\Invalidator\InvalidatorCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use \Mockery;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class InvalidationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoRoutesInvalidatedWhenResponseIsUnsuccessful()
    {
        $cacheManager = \Mockery::mock('\FOS\HttpCacheBundle\CacheManager')
            ->shouldDeferMissing()
            ->shouldReceive('invalidateRoute')
            ->never()
            ->getMock();

        $invalidators = \Mockery::mock('\FOS\HttpCacheBundle\Invalidator\InvalidatorCollection')
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
        $cacheManager = \Mockery::mock('\FOS\HttpCacheBundle\CacheManager');
        $cacheManager->shouldReceive('invalidatePath')->with('/retrieve/something/123')
            ->shouldReceive('invalidatePath')->with('/retrieve/something/123/bla')
            ->shouldReceive('flush')->once()
            ->getMock();

        $routes = new RouteCollection();
        $routes->add('route_invalidator', new Route('/edit/something/{id}/{special}'));
        $routes->add('route_invalidated', new Route('/retrieve/something/{id}'));
        $routes->add('route_invalidated_special', new Route('/retrieve/something/{id}/{special}'));

        $requestParams = array('id' => 123, 'special' => 'bla');
        $router = \Mockery::mock('\Symfony\Component\Routing\Router')
            ->shouldDeferMissing()
            ->shouldReceive('generate')
            ->with('route_invalidated', $requestParams)
            ->andReturn('/retrieve/something/123?special=bla')

            ->shouldReceive('generate')
            ->with('route_invalidated_special', $requestParams)
            ->andReturn('/retrieve/something/123/bla')
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
        $request->attributes->set('_route_params', $requestParams);

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