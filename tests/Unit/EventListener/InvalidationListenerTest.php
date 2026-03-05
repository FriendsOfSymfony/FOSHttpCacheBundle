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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\AttributesRequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class InvalidationListenerTest extends TestCase
{
    private CacheManager&MockObject $cacheManager;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private RuleMatcherInterface&MockObject $mustInvalidateRule;
    private InvalidationListener $listener;

    public function setUp(): void
    {
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->mustInvalidateRule = $this->createMock(RuleMatcherInterface::class);
        $this->mustInvalidateRule->method('matches')->willReturn(true);

        $this->listener = new InvalidationListener(
            $this->cacheManager,
            $this->urlGenerator,
            $this->mustInvalidateRule
        );
    }

    public function testNoRoutesInvalidatedWhenResponseIsUnsuccessful(): void
    {
        $this->cacheManager
            ->expects($this->never())
            ->method('invalidateRoute');
        $this->cacheManager
            ->expects($this->once())
            ->method('flush');

        $request = new Request();
        $request->attributes->set('_route', 'my_route');

        $event = $this->getEvent($request, new Response('', 500));
        $this->listener->onKernelTerminate($event);
    }

    public function testOnKernelTerminate(): void
    {
        $invalidatePathIndex = 0;
        $this->cacheManager
            ->expects($this->exactly(2))
            ->method('invalidatePath')
            ->willReturnCallback(function (string $path) use (&$invalidatePathIndex): CacheManager {
                self::assertSame([
                    '/retrieve/something/123',
                    '/retrieve/something/123/bla',
                ][$invalidatePathIndex++], $path);

                return $this->cacheManager;
            });
        $this->cacheManager
            ->expects($this->once())
            ->method('flush');

        $routes = new RouteCollection();
        $routes->add('route_invalidator', new Route('/edit/something/{id}/{special}'));
        $routes->add('route_invalidated', new Route('/retrieve/something/{id}'));
        $routes->add('route_invalidated_special', new Route('/retrieve/something/{id}/{special}'));

        $requestParams = ['id' => 123, 'special' => 'bla'];
        $generateIndex = 0;
        $this->urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function (string $name, array $params, int $referenceType) use ($requestParams, &$generateIndex): string {
                [$expectedName, $expectedParams, $expectedReferenceType, $returnValue] = [
                    ['route_invalidated', $requestParams, UrlGeneratorInterface::ABSOLUTE_PATH, '/retrieve/something/123?special=bla'],
                    ['route_invalidated_special', $requestParams, UrlGeneratorInterface::ABSOLUTE_PATH, '/retrieve/something/123/bla'],
                ][$generateIndex++];
                self::assertSame($expectedName, $name);
                self::assertSame($expectedParams, $params);
                self::assertSame($expectedReferenceType, $referenceType);

                return $returnValue;
            });

        $requestMatcher = new AttributesRequestMatcher(
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

    public function testAbsoluteUrl(): void
    {
        $this->cacheManager
            ->expects($this->once())
            ->method('invalidatePath')
            ->with('http://localhost/retrieve/something/123')
            ->willReturnSelf();
        $this->cacheManager
            ->expects($this->once())
            ->method('flush');

        $routes = new RouteCollection();
        $routes->add('route_invalidated', new Route('/retrieve/something/{id}'));

        $requestParams = ['id' => 123, 'special' => 'bla'];
        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('route_invalidated', $requestParams, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/retrieve/something/123?special=bla');

        $requestMatcher = new AttributesRequestMatcher(
            ['_route' => 'route_invalidator']
        );

        $request = new Request();
        $request->attributes->set('_route', 'route_invalidator');
        $request->attributes->set('_route_params', $requestParams);

        $event = $this->getEvent($request);

        $listener = new InvalidationListener(
            $this->cacheManager,
            $this->urlGenerator,
            $this->mustInvalidateRule,
            null,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $listener->addRule($requestMatcher, [
            'route_invalidated' => ['ignore_extra_params' => true],
        ]);
        $listener->onKernelTerminate($event);
    }

    public function testOnKernelException(): void
    {
        $this->cacheManager
            ->expects($this->once())
            ->method('flush');
        $event = $this->getEvent(new Request());
        $this->listener->onKernelException($event);
    }

    public function testInvalidatePath(): void
    {
        $request = Request::create('', 'PUT');
        $request->attributes->set('_invalidate_path', [
            new InvalidatePath(['value' => '/some/path']),
            new InvalidatePath(['value' => ['/other/path', 'http://absolute.com/path']]),
        ]);

        $event = $this->getEvent($request);

        $invalidatePathIndex = 0;
        $this->cacheManager
            ->expects($this->exactly(3))
            ->method('invalidatePath')
            ->willReturnCallback(function (string $path) use (&$invalidatePathIndex): CacheManager {
                self::assertSame([
                    '/some/path',
                    '/other/path',
                    'http://absolute.com/path',
                ][$invalidatePathIndex++], $path);

                return $this->cacheManager;
            });
        $this->cacheManager
            ->expects($this->once())
            ->method('flush');

        $this->listener->onKernelTerminate($event);
    }

    public function testInvalidateRoute(): void
    {
        $request = Request::create('', 'POST');
        $request->attributes->set('request_id', 123);
        $request->attributes->set('_invalidate_route', [
            new InvalidateRoute(['name' => 'some_route']),
            new InvalidateRoute(['name' => 'other_route', 'params' => ['id' => ['expression' => 'request_id']]]),
        ]);

        $event = $this->getEvent($request);

        $invalidateRouteIndex = 0;
        $this->cacheManager
            ->expects($this->exactly(2))
            ->method('invalidateRoute')
            ->willReturnCallback(function (string $name, array $params) use (&$invalidateRouteIndex): CacheManager {
                [$expectedName, $expectedParams] = [
                    ['some_route', []],
                    ['other_route', ['id' => 123]],
                ][$invalidateRouteIndex++];
                self::assertSame($expectedName, $name);
                self::assertSame($expectedParams, $params);

                return $this->cacheManager;
            });
        $this->cacheManager
            ->expects($this->once())
            ->method('flush');

        $this->listener->onKernelTerminate($event);
    }

    public function testOnConsoleTerminate(): void
    {
        $this->cacheManager
            ->expects($this->once())
            ->method('flush')
            ->willReturn(2);

        $output = $this->createMock(OutputInterface::class);
        $output
            ->expects($this->once())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_VERBOSE);
        $output
            ->expects($this->once())
            ->method('writeln')
            ->with('Sent 2 invalidation request(s)');

        $event = $this->createMock(ConsoleEvent::class);
        $event
            ->expects($this->exactly(2))
            ->method('getOutput')
            ->willReturn($output);

        $this->listener->onConsoleTerminate($event);
    }

    protected function getEvent(Request $request, ?Response $response = null): TerminateEvent
    {
        return new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $response ?? new Response()
        );
    }
}
