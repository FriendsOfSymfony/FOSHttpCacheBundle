<?php

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\EventListener\CacheAuthorizationSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CacheAuthorizationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testHeadRequest()
    {
        $request = new Request();
        $request->setMethod('HEAD');
        $event = $this->getEvent($request);

        $listener = new CacheAuthorizationSubscriber();
        $listener->onKernelRequest($event);
        $this->assertTrue($event->hasResponse());
    }

    public function testNonHeadRequest()
    {
        $request = new Request();
        $request->setMethod('GET');
        $event = $this->getEvent($request);

        $listener = new CacheAuthorizationSubscriber();
        $listener->onKernelRequest($event);
        $this->assertFalse($event->hasResponse());
    }

    protected function getEvent(Request $request, Response $response = null)
    {
        return new GetResponseEvent(
            \Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            null !== $response ? $response : new Response()
        );
    }
}
