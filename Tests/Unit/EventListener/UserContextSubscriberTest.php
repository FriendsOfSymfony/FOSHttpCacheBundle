<?php

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCache\UserContext\HashGenerator;
use FOS\HttpCacheBundle\EventListener\UserContextSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class UserContextSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testOnKernelRequest()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn(true);

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, new HashGenerator(), 'X-SessionId', 'X-Hash');
        $event = $this->getKernelRequestEvent($request);

        $userContextSubscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $hash = hash('sha256', serialize(array()));


        $this->assertNotNull($response);
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($hash, $response->headers->get('X-Hash'));
        $this->assertNull($response->headers->get('Vary'));
        $this->assertEquals('max-age=0, private', $response->headers->get('Cache-Control'));
    }

    public function testOnKernelRequestCached()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn(true);

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, new HashGenerator(), 'X-SessionId', 'X-Hash', 30);
        $event = $this->getKernelRequestEvent($request);

        $userContextSubscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $hash = hash('sha256', serialize(array()));

        $this->assertNotNull($response);
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($hash, $response->headers->get('X-Hash'));
        $this->assertEquals('X-SessionId', $response->headers->get('Vary'));
        $this->assertEquals('max-age=30, private', $response->headers->get('Cache-Control'));
    }

    public function testOnKernelRequestNotMatched()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn(false);

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, new HashGenerator(), 'X-SessionId', 'X-Hash');
        $event = $this->getKernelRequestEvent($request);

        $userContextSubscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $hash = hash('sha256', serialize(array()));

        $this->assertNull($response);
    }

    public function testOnKernelResponse()
    {
        $request = new Request();
        $request->setMethod('HEAD');
        $request->headers->set('X-Hash', 'hash');

        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn(false);

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, new HashGenerator(), 'X-SessionId', 'X-Hash');
        $event = $this->getKernelResponseEvent($request);

        $userContextSubscriber->onKernelResponse($event);

        $this->assertContains('X-Hash', $event->getResponse()->headers->get('Vary'));
    }

    public function testOnKernelResponseNotCached()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn(false);

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, new HashGenerator(), 'X-SessionId', 'X-Hash');
        $event = $this->getKernelResponseEvent($request);

        $userContextSubscriber->onKernelResponse($event);

        $this->assertNull($event->getResponse()->headers->get('Vary'));
    }

    public function testOnKernelResponseOnHashRequest()
    {
        $request = new Request();
        $request->setMethod('HEAD');
        $request->headers->set('X-Hash', 'hash');

        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn(true);

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, new HashGenerator(), 'X-SessionId', 'X-Hash');
        $event = $this->getKernelResponseEvent($request);

        $userContextSubscriber->onKernelResponse($event);

        $this->assertNull($event->getResponse()->headers->get('Vary'));
    }

    protected function getKernelRequestEvent(Request $request)
    {
        return new GetResponseEvent(
            \Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    protected function getKernelResponseEvent(Request $request, Response $response = null)
    {
        return new FilterResponseEvent(
            \Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response ?: new Response()
        );
    }
}
