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

use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use FOS\HttpCacheBundle\EventListener\CacheControlSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CacheControlSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultHeaders()
    {
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $headers = array( 'controls' => array(
            'etag' => '1337eax',
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
        $this->assertEquals('"1337eax"', $newHeaders['etag'][0]);
    }

    public function testExtraHeaders()
    {
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
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
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
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
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
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

    public function testVary()
    {
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $headers = array('vary' => array(
            'Cookie',
            'Accept-Language',
            'Encoding',
        ));
        $listener->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['vary']), implode(',', array_keys($newHeaders)));
        $this->assertEquals($headers['vary'], $newHeaders['vary']);
    }

    public function testReverseProxyTtl()
    {
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $headers = array(
            'reverse_proxy_ttl' => 600,
        );
        $listener->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['x-reverse-proxy-ttl']), implode(',', array_keys($newHeaders)));
        $this->assertEquals(600, $newHeaders['x-reverse-proxy-ttl'][0]);
    }

    public function testDebug()
    {
        $listener = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->setConstructorArgs(array(null, 'X-Cache-Debug'))
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $listener->expects($this->once())->method('getOptions')->will($this->returnValue(array()));

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['x-cache-debug']), implode(',', array_keys($newHeaders)));
        $this->assertTrue(isset($newHeaders['x-cache-debug'][0]));
    }

    public function testConfigDefineRequestMatcherWithControllerName()
    {
        $extension = new FOSHttpCacheExtension();
        $container = new ContainerBuilder();

        $extension->load(array(
            array('rules' => array(
                array(
                    'attributes' => array(
                        '_controller' => '^AcmeBundle:Default:index$',
                    ),
                )
            )
        )), $container);

        // Extract the corresponding definition
        $matcherDefinition = null;
        foreach ($container->getDefinitions() as $definition) {
            if ($definition instanceof DefinitionDecorator &&
                $definition->getParent() === 'fos_http_cache.request_matcher'
            ) {
                if ($matcherDefinition) {
                    $this->fail('More then one request matcher was created');
                }
                $matcherDefinition = $definition;
            }
        }

        // definition should exist
        $this->assertNotNull($matcherDefinition);

        // 4th argument should contain the controller name value
        $this->assertEquals(array('_controller' => '^AcmeBundle:Default:index$'), $matcherDefinition->getArgument(4));
    }

    public function testMatchRuleWithActionName()
    {
        $listener = new \FOS\HttpCacheBundle\EventListener\CacheControlSubscriber();

        $headers = array( 'controls' => array(
            'etag' => '1337',
            'last_modified' => '13.07.2003',
            'max_age' => '900',
            's_maxage' => '300',
            'public' => true,
            'private' => false
        ));

        $listener->add(
            new RequestMatcher(null, null, null, null, array('_controller' => '^AcmeBundle:Default:index$')),
            $headers
        );

        // Request with a matching controller name
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $request = new Request();
        $request->attributes->set('_controller', 'AcmeBundle:Default:index');
        $response = new Response();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);
        $this->assertEquals(strtotime('13.07.2003'), strtotime($newHeaders['last-modified'][0]));

        // Request with a non-matching controller name
        $request = new Request();
        $request->attributes->set('_controller', 'AcmeBundle:Default:notIndex');
        $response = new Response();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('no-cache', $newHeaders['cache-control'][0]);
    }

    public function testUnlessRole()
    {
        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $listener = new CacheControlSubscriber($security);
        $listener->add(
            new RequestMatcher(),
            array(
                'controls' => array('public' => true),
                'unless_role' => 'ROLE_NO_CACHE',
            )
        );

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();

        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $listener->onKernelResponse($event);
        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['cache-control']), implode(',', array_keys($newHeaders)));
        $this->assertEquals('public', $newHeaders['cache-control'][0]);

        // now with security saying we have the unless_role
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_NO_CACHE')
            ->will($this->returnValue(true))
        ;
        $response = new Response();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);
        $listener->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['cache-control']), implode(',', array_keys($newHeaders)));
        $this->assertNotContains('public', $newHeaders['cache-control'][0]);
    }
}
