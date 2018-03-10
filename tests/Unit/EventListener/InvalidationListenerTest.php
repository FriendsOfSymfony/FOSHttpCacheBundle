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

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\EventListener\InvalidationListener;
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class InvalidationListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CacheManager|MockInterface
     */
    private $cacheManager;

    /**
     * @var UrlGeneratorInterface|MockInterface
     */
    private $urlGenerator;

    /**
     * @var RuleMatcherInterface|MockInterface
     */
    private $mustInvalidateRule;

    /**
     * @var InvalidationListener
     */
    private $listener;

    public function setUp()
    {
        $this->cacheManager = \Mockery::mock(CacheManager::class);
        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $this->mustInvalidateRule = \Mockery::mock(RuleMatcherInterface::class)
            ->shouldReceive('matches')
            ->andReturn(true)
            ->getMock();

        $this->listener = new InvalidationListener(
            $this->cacheManager,
            $this->urlGenerator,
            $this->mustInvalidateRule
        );
    }

    public function testNoRoutesInvalidatedWhenResponseIsUnsuccessful()
    {
        $this->cacheManager
            ->shouldReceive('invalidateRoute')->never()
            ->shouldReceive('flush')->once();

        $request = new Request();
        $request->attributes->set('_route', 'my_route');

        $event = $this->getEvent($request, new Response('', 500));
        $this->listener->onKernelTerminate($event);
    }

    public function testOnKernelTerminate()
    {
        $this->cacheManager
            ->shouldReceive('invalidatePath')->with('/retrieve/something/123')
            ->shouldReceive('invalidatePath')->with('/retrieve/something/123/bla')
            ->shouldReceive('flush')->once()
            ->getMock();

        $routes = new RouteCollection();
        $routes->add('route_invalidator', new Route('/edit/something/{id}/{special}'));
        $routes->add('route_invalidated', new Route('/retrieve/something/{id}'));
        $routes->add('route_invalidated_special', new Route('/retrieve/something/{id}/{special}'));

        $requestParams = ['id' => 123, 'special' => 'bla'];
        $this->urlGenerator
            ->shouldDeferMissing()
            ->shouldReceive('generate')
            ->with('route_invalidated', $requestParams)
            ->andReturn('/retrieve/something/123?special=bla')

            ->shouldReceive('generate')
            ->with('route_invalidated_special', $requestParams)
            ->andReturn('/retrieve/something/123/bla')
            ->getMock();

        $requestMatcher = new RequestMatcher(
            null,
            null,
            null,
            null,
            ['_route' => 'route_invalidator']
        );

        $this->listener->addRule($requestMatcher, [
            'route_invalidated' => ['ignore_extra_params' => true],
            'route_invalidated_special' => ['ignore_extra_params' => true],
        ]);

        $request = new Request();
        $request->attributes->set('_route', 'route_invalidator');
        $request->attributes->set('_route_params', $requestParams);

        $event = $this->getEvent($request);
        $this->listener->onKernelTerminate($event);
    }

    public function testOnKernelException()
    {
        $this->cacheManager->shouldReceive('flush')->once();
        $event = $this->getEvent(new Request());
        $this->listener->onKernelException($event);
    }

    public function testInvalidatePath()
    {
        $request = Request::create('', 'PUT');
        $request->attributes->set('_invalidate_path', [
            new InvalidatePath(['value' => '/some/path']),
            new InvalidatePath(['value' => ['/other/path', 'http://absolute.com/path']]),
        ]);

        $event = $this->getEvent($request);

        $this->cacheManager
            ->shouldReceive('invalidatePath')->with('/some/path')->once()
            ->shouldReceive('invalidatePath')->with('/other/path')->once()
            ->shouldReceive('invalidatePath')->with('http://absolute.com/path')->once()
            ->shouldReceive('flush')->once();

        $this->listener->onKernelTerminate($event);
    }

    public function testInvalidateRoute()
    {
        $request = Request::create('', 'POST');
        $request->attributes->set('request_id', 123);
        $request->attributes->set('_invalidate_route', [
            new InvalidateRoute(['name' => 'some_route']),
            new InvalidateRoute(['name' => 'other_route', 'params' => ['id' => ['expression' => 'request_id']]]),
        ]);

        $event = $this->getEvent($request);

        $this->cacheManager
            ->shouldReceive('invalidateRoute')->with('some_route', [])->once()
            ->shouldReceive('invalidateRoute')->with('other_route', ['id' => 123])->once()
            ->shouldReceive('flush')->once();

        $this->listener->onKernelTerminate($event);
    }

    public function testOnConsoleTerminate()
    {
        $this->cacheManager->shouldReceive('flush')->once()->andReturn(2);

        $output = \Mockery::mock(OutputInterface::class)
            ->shouldReceive('getVerbosity')->once()->andReturn(OutputInterface::VERBOSITY_VERBOSE)
            ->shouldReceive('writeln')->with('Sent 2 invalidation request(s)')->once()
            ->getMock();

        $event = \Mockery::mock(ConsoleEvent::class)
            ->shouldReceive('getOutput')->andReturn($output)
            ->getMock();

        $this->listener->onConsoleTerminate($event);
    }

    protected function getEvent(Request $request, Response $response = null)
    {
        return new PostResponseEvent(
            \Mockery::mock(HttpKernelInterface::class),
            $request,
            null !== $response ? $response : new Response()
        );
    }
}
