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

use FOS\HttpCacheBundle\EventListener\SessionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @group sf34
 */
class SessionListenerTest extends TestCase
{
    public function testOnKernelRequestRemainsUntouched()
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $inner = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\EventListener\SessionListener')
            ->disableOriginalConstructor()
            ->getMock();

        $inner
            ->expects($this->once())
            ->method('onKernelRequest')
            ->with($event)
        ;

        $listener = $this->getListener($inner);
        $listener->onKernelRequest($event);
    }

    public function testOnFinishRequestRemainsUntouched()
    {
        if (!method_exists('Symfony\Component\HttpKernel\EventListener\SessionListener', 'onFinishRequest')) {
            $this->markTestSkipped('Method onFinishRequest does not exist on Symfony\Component\HttpKernel\EventListener\SessionListener');
        }

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\FinishRequestEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $inner = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\EventListener\SessionListener')
            ->disableOriginalConstructor()
            ->getMock();

        $inner
            ->expects($this->once())
            ->method('onFinishRequest')
            ->with($event)
        ;

        $listener = $this->getListener($inner);
        $listener->onFinishRequest($event);
    }

    /**
     * @dataProvider onKernelResponseProvider
     */
    public function testOnKernelResponse(Response $response, $shouldCallDecoratedListener)
    {
        if (version_compare(Kernel::VERSION, '3.4', '<')) {
            $this->markTestSkipped('Irrelevant for Symfony < 3.4');
        }

        $httpKernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new FilterResponseEvent(
            $httpKernel,
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $inner = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\EventListener\SessionListener')
            ->disableOriginalConstructor()
            ->getMock();

        $inner
            ->expects($shouldCallDecoratedListener ? $this->once() : $this->never())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $listener = $this->getListener($inner);
        $listener->onKernelResponse($event);
    }

    public function onKernelResponseProvider()
    {
        // Response, decorated listener should be called or not
        return array(
            'Irrelevant response' => array(new Response(), true),
            'Irrelevant response header' => array(new Response('', 200, array('Content-Type' => 'Foobar')), true),
            'Context hash header is present in Vary' => array(new Response('', 200, array('Vary' => 'X-User-Context-Hash')), false),
            'User identifier header is present in Vary' => array(new Response('', 200, array('Vary' => 'cookie')), false),
            'Both, context hash and identifier headers are present in Vary' => array(new Response('', 200, array('Vary' => 'Cookie, X-User-Context-Hash')), false),
        );
    }

    private function getListener(BaseSessionListener $inner, $userHashHeader = 'x-user-context-hash', $userIdentifierHeaders = array('cookie', 'authorization'))
    {
        return new SessionListener($inner, $userHashHeader, $userIdentifierHeaders);
    }
}
