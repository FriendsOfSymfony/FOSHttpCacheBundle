<?php

namespace LiipCacheControlBundle\Test\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Liip\CacheControlBundle\EventListener\CacheControlListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class CacheControlListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultHeaders()
    {
        $listener = $this->getMockBuilder('Liip\CacheControlBundle\EventListener\CacheControlListener')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $headers = array( 'controls' => array(
            'etag' => '1337',
            'last_modified' => '13.07.2003',
            'max_age' => '900',
            's_maxage' => '300',
            'public' => true,
            'private' => false
        ));

        $listener->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);
        $this->assertEquals(strtotime('13.07.2003'), strtotime($newHeaders['last-modified'][0]));
    }

    public function testExtraHeaders()
    {
        $listener = $this->getMockBuilder('Liip\CacheControlBundle\EventListener\CacheControlListener')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $headers = array( 'controls' => array(
            'must_revalidate' => true,
            'proxy_revalidate' => true,
            'no_transform' => true,
            'stale_if_error' => '300',
            'stale_while_revalidate' => '400',
        ));

        $listener->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('must-revalidate, no-transform, proxy-revalidate, stale-if-error=300, stale-while-revalidate=400, private', $newHeaders['cache-control'][0]);
    }

    public function testCompoundHeaders()
    {
        $listener = $this->getMockBuilder('Liip\CacheControlBundle\EventListener\CacheControlListener')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $headers = array( 'controls' => array(
            'etag' => '1337',
            'last_modified' => '13.07.2003',
            'max_age' => '900',
            's_maxage' => '300',
            'public' => true,
            'private' => false,
            'must_revalidate' => true,
            'proxy_revalidate' => true,
            'no_transform' => true,
            'stale_if_error' => '300',
            'stale_while_revalidate' => '400',
        ));

        $listener->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('max-age=900, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=300, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
    }

    public function testSetNoCacheHeaders()
    {
        $listener = $this->getMockBuilder('Liip\CacheControlBundle\EventListener\CacheControlListener')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $headers = array( 'controls' => array(
            'etag' => '1337',
            'last_modified' => '13.07.2003',
            'max_age' => '900',
            's_maxage' => '300',
            'public' => true,
            'private' => false,
            'no_cache' => true,
            'must_revalidate' => true,
            'proxy_revalidate' => true,
            'no_transform' => true,
            'stale_if_error' => '300',
            'stale_while_revalidate' => '400',
        ));

        $listener->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('no-cache, private', $newHeaders['cache-control'][0]);
    }

}
