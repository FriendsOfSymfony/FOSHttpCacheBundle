<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\EventListener\CacheAuthorizationSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Mockery;

/**
 * Class CacheAuthorizationSubscriberTest
 */
class CacheAuthorizationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test head request
     */
    public function testHeadRequest()
    {
        $request = new Request();
        $request->setMethod('HEAD');
        $event = $this->getEvent($request);

        $listener = new CacheAuthorizationSubscriber();
        $listener->onKernelRequest($event);
        $this->assertTrue($event->hasResponse());
    }

    /**
     * Test non head request
     */
    public function testNonHeadRequest()
    {
        $request = new Request();
        $request->setMethod('GET');
        $event = $this->getEvent($request);

        $listener = new CacheAuthorizationSubscriber();
        $listener->onKernelRequest($event);
        $this->assertFalse($event->hasResponse());
    }

    /**
     * Get event
     *
     * @param Request  $request  Request
     * @param Response $response Response
     *
     * @return GetResponseEvent
     */
    protected function getEvent(Request $request, Response $response = null)
    {
        return new GetResponseEvent(
            Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            null !== $response ? $response : new Response()
        );
    }
}
