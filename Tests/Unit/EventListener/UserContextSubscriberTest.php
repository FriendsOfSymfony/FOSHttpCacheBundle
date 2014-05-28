<?php

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCache\UserContext\HashGenerator;
use FOS\HttpCacheBundle\EventListener\UserContextSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class UserContextSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testOnKernelRequest()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = $this->getRequestMatcher($request, true);
        $hashGenerator = \Mockery::mock('\FOS\HttpCache\UserContext\HashGenerator');
        $hashGenerator->shouldReceive('generateHash')->andReturn('hash');

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, $hashGenerator, array('X-SessionId'), 'X-Hash');
        $event = $this->getKernelRequestEvent($request);

        $userContextSubscriber->onKernelRequest($event);

        $response = $event->getResponse();


        $this->assertNotNull($response);
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals('hash', $response->headers->get('X-Hash'));
        $this->assertNull($response->headers->get('Vary'));
        $this->assertEquals('max-age=0, no-cache, private', $response->headers->get('Cache-Control'));
    }

    public function testOnKernelRequestCached()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = $this->getRequestMatcher($request, true);
        $hashGenerator = \Mockery::mock('\FOS\HttpCache\UserContext\HashGenerator');
        $hashGenerator->shouldReceive('generateHash')->andReturn('hash');

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, $hashGenerator, array('X-SessionId'), 'X-Hash', 30);
        $event = $this->getKernelRequestEvent($request);

        $userContextSubscriber->onKernelRequest($event);

        $response = $event->getResponse();

        $this->assertNotNull($response);
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals('hash', $response->headers->get('X-Hash'));
        $this->assertEquals('X-SessionId', $response->headers->get('Vary'));
        $this->assertEquals('max-age=30, private', $response->headers->get('Cache-Control'));
    }

    public function testOnKernelRequestNotMatched()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = $this->getRequestMatcher($request, false);
        $hashGenerator = \Mockery::mock('\FOS\HttpCache\UserContext\HashGenerator');

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, $hashGenerator, array('X-SessionId'), 'X-Hash');
        $event = $this->getKernelRequestEvent($request);

        $userContextSubscriber->onKernelRequest($event);

        $response = $event->getResponse();

        $this->assertNull($response);
    }

    public function testOnKernelResponse()
    {
        $request = new Request();
        $request->setMethod('HEAD');
        $request->headers->set('X-Hash', 'hash');

        $requestMatcher = $this->getRequestMatcher($request, false);
        $hashGenerator = \Mockery::mock('\FOS\HttpCache\UserContext\HashGenerator');

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, $hashGenerator, array('X-SessionId'), 'X-Hash');
        $event = $this->getKernelResponseEvent($request);

        $userContextSubscriber->onKernelResponse($event);

        $this->assertContains('X-Hash', $event->getResponse()->headers->get('Vary'));
    }

    /**
     * If there is no hash in the request, vary on the user identifier.
     */
    public function testOnKernelResponseNotCached()
    {
        $request = new Request();
        $request->setMethod('HEAD');

        $requestMatcher = $this->getRequestMatcher($request, false);
        $hashGenerator = \Mockery::mock('\FOS\HttpCache\UserContext\HashGenerator');

        $userContextSubscriber = new UserContextSubscriber($requestMatcher, $hashGenerator, array('X-SessionId'), 'X-Hash');
        $event = $this->getKernelResponseEvent($request);

        $userContextSubscriber->onKernelResponse($event);

        $this->assertEquals('X-SessionId', $event->getResponse()->headers->get('Vary'));
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

    /**
     * @param Request $request
     * @param bool    $match
     *
     * @return \Mockery\MockInterface|RequestMatcherInterface
     */
    private function getRequestMatcher(Request $request, $match)
    {
        $requestMatcher = \Mockery::mock('\Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $requestMatcher->shouldReceive('matches')->with($request)->andReturn($match);

        return $requestMatcher;
    }
}
