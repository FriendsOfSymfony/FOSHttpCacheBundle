<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\EventListener\InvalidationSubscriber;
use FOS\HttpCacheBundle\Http\RuleMatcher;
use FOS\HttpCacheBundle\Invalidator\Invalidator;
use FOS\HttpCacheBundle\Invalidator\InvalidatorCollection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use \Mockery;

class InvalidationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheManager;

    public function setUp()
    {
        $this->cacheManager = \Mockery::mock('\FOS\HttpCacheBundle\CacheManager');
    }

    public function testNoRoutesInvalidatedWhenResponseIsUnsuccessful()
    {
        $this->cacheManager
            ->shouldReceive('invalidateRoute')->never()
            ->shouldReceive('flush')->once();

        $this->invalidators = \Mockery::mock('\FOS\HttpCacheBundle\Invalidator\InvalidatorCollection')
            ->shouldReceive('hasInvalidatorRoute')
            ->with('my_route')
            ->andReturn(false)
            ->getMock();

        $request = new Request();
        $request->attributes->set('_route', 'my_route');

        $event = $this->getEvent($request, new Response('', 500));
        $this->getListener()->onKernelTerminate($event);
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

        $ruleMatcher = new RuleMatcher(new RequestMatcher(
            null,
            null,
            null,
            null,
            array('_route' => 'route_invalidator')
        ), array());

        $listener = new InvalidationSubscriber($cacheManager, $router);
        $listener->addRule($ruleMatcher, array(
            'route_invalidated' => array('ignore_extra_params' => true),
            'route_invalidated_special' => array('ignore_extra_params' => true),
        ));

        $request = new Request();
        $request->attributes->set('_route', 'route_invalidator');
        $request->attributes->set('_route_params', $requestParams);

        $event = $this->getEvent($request);
        $listener->onKernelTerminate($event);
    }

    public function testOnKernelException()
    {
        $cacheManager = \Mockery::mock('\FOS\HttpCacheBundle\CacheManager');
        $cacheManager
            ->shouldReceive('flush')->once()
        ;

        $router = \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $listener = new InvalidationSubscriber($cacheManager, $router);

        $request = new Request();

        $event = $this->getEvent($request);
        $listener->onKernelException($event);
    }

    public function testInvalidatePath()
    {
        $request = new Request();
        $request->attributes->set('_invalidate_path', array(
            new InvalidatePath(array('value' => '/some/path')),
            new InvalidatePath(array('value' => array('/other/path', 'http://absolute.com/path')))
        ));

        $event = $this->getEvent($request);

        $this->cacheManager
            ->shouldReceive('invalidatePath')->with('/some/path')->once()
            ->shouldReceive('invalidatePath')->with('/other/path')->once()
            ->shouldReceive('invalidatePath')->with('http://absolute.com/path')->once()
            ->shouldReceive('flush')->once();

        $this->getListener()->onKernelTerminate($event);
    }

    public function testInvalidateRoute()
    {
        $request = new Request();
        $request->attributes->set('request_id', 123);
        $request->attributes->set('_invalidate_route', array(
            new InvalidateRoute(array('name' => 'some_route')),
            new InvalidateRoute(array('name' => 'other_route', 'params' => array('id' => array('expression' => 'request_id'))))
        ));

        $event = $this->getEvent($request);

        $this->cacheManager
            ->shouldReceive('invalidateRoute')->with('some_route', array())->once()
            ->shouldReceive('invalidateRoute')->with('other_route', array('id' => 123))->once()
            ->shouldReceive('flush')->once();

        $this->getListener()->onKernelTerminate($event);
    }

    public function testOnConsoleTerminate()
    {
        $this->cacheManager->shouldReceive('flush')->once()->andReturn(2);

        $output = \Mockery::mock('\Symfony\Component\Console\Output\OutputInterface')
            ->shouldReceive('getVerbosity')->once()->andReturn(OutputInterface::VERBOSITY_VERBOSE)
            ->shouldReceive('writeln')->with('Sent 2 invalidation request(s)')->once()
            ->getMock();

        $event = \Mockery::mock('\Symfony\Component\Console\Event\ConsoleEvent')
            ->shouldReceive('getOutput')->andReturn($output)
            ->getMock();

        $this->getListener()->onConsoleTerminate($event);
    }

    protected function getEvent(Request $request, Response $response = null)
    {
        return new PostResponseEvent(
            \Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            null !== $response ? $response : new Response()
        );
    }

    protected function getListener()
    {
        return new InvalidationSubscriber(
            $this->cacheManager,
            \Mockery::mock('\Symfony\Component\Routing\Generator\UrlGeneratorInterface')
        );
    }
}
