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
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

if (Kernel::MAJOR_VERSION >= 5) {
    class_alias(RequestEvent::class, 'FOS\HttpCacheBundle\Tests\Unit\EventListener\SessionRequestEvent');
    class_alias(ResponseEvent::class, 'FOS\HttpCacheBundle\Tests\Unit\EventListener\SessionResponseEvent');
} else {
    class_alias(GetResponseEvent::class, 'FOS\HttpCacheBundle\Tests\Unit\EventListener\SessionRequestEvent');
    class_alias(FilterResponseEvent::class, 'FOS\HttpCacheBundle\Tests\Unit\EventListener\SessionResponseEvent');
}

class SessionListenerTest extends TestCase
{
    public function testOnKernelRequestRemainsUntouched()
    {
        $event = $this->createMock(SessionRequestEvent::class);
        $inner = $this->createMock(BaseSessionListener::class);

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
        if (!method_exists(BaseSessionListener::class, 'onFinishRequest')) {
            $this->markTestSkipped('Method onFinishRequest does not exist on '.BaseSessionListener::class);
        }

        $event = new FinishRequestEvent($this->createMock(KernelInterface::class), $this->createMock(Request::class), HttpKernelInterface::MASTER_REQUEST);
        $inner = $this->createMock(BaseSessionListener::class);

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
    public function testOnKernelResponse(Response $response, bool $shouldCallDecoratedListener)
    {
        if (version_compare(Kernel::VERSION, '3.4', '<')) {
            $this->markTestSkipped('Irrelevant for Symfony < 3.4');
        }

        $event = new SessionResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $inner = $this->createMock(BaseSessionListener::class);

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
        return [
            'Irrelevant response' => [new Response(), true],
            'Irrelevant response header' => [new Response('', 200, ['Content-Type' => 'Foobar']), true],
            'Context hash header is present in Vary' => [new Response('', 200, ['Vary' => 'X-User-Context-Hash']), false],
            'User identifier header is present in Vary' => [new Response('', 200, ['Vary' => 'cookie']), false],
            'Both, context hash and identifier headers are present in Vary' => [new Response('', 200, ['Vary' => 'Cookie, X-User-Context-Hash']), false],
        ];
    }

    private function getListener(BaseSessionListener $inner, $userHashHeader = 'x-user-context-hash', $userIdentifierHeaders = ['cookie', 'authorization'])
    {
        return new SessionListener($inner, $userHashHeader, $userIdentifierHeaders);
    }
}
