<?php

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
        $event = $this->buildEvent();
        $headers = array(
            'last_modified' => '13.07.2003',
            'cache_control' => array(
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false
            )
        );
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);
        $this->assertArrayHasKey('last-modified', $newHeaders, implode(',', array_keys($newHeaders)));
        $this->assertEquals(strtotime('13.07.2003'), strtotime($newHeaders['last-modified'][0]));
    }

    public function testExtraHeaders()
    {
        $event = $this->buildEvent();
        $headers = array('cache_control' => array(
            'must_revalidate' => true,
            'proxy_revalidate' => true,
            'no_transform' => true,
            'stale_if_error' => '300',
            'stale_while_revalidate' => '400',
        ));
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('must-revalidate, no-transform, proxy-revalidate, stale-if-error=300, stale-while-revalidate=400, private', $newHeaders['cache-control'][0]);
    }

    public function testCompoundHeaders()
    {
        $event = $this->buildEvent();
        $headers = array('cache_control' => array(
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
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=300, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
    }

    public function testSetNoCacheHeaders()
    {
        $event = $this->buildEvent();
        $headers = array(
            'cache_control' => array(
                'max_age' => '0',
                's_maxage' => '0',
                'private' => true,
                'no_cache' => true,
                'must_revalidate' => true,
            ),
            'last_modified' => '13.07.2003',
        );
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=0, must-revalidate, no-cache, private, s-maxage=0', $newHeaders['cache-control'][0]);
    }

    /**
     * Note that this has the side effect of adding "private" to the cache directives.
     */
    public function testSetOnlyNoCacheHeader()
    {
        $event = $this->buildEvent();
        $headers = array(
            'cache_control' => array(
                'no_cache' => true,
            ),
        );
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('no-cache, private', $newHeaders['cache-control'][0]);
    }

    public function testVary()
    {
        $event = $this->buildEvent();
        $headers = array('vary' => array(
            'Cookie',
            'Accept-Language',
            'Encoding',
        ));
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['vary']), implode(',', array_keys($newHeaders)));
        $this->assertEquals($headers['vary'], $newHeaders['vary']);
    }

    public function testReverseProxyTtl()
    {
        $event = $this->buildEvent();
        $headers = array(
            'reverse_proxy_ttl' => 600,
        );
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['x-reverse-proxy-ttl']), implode(',', array_keys($newHeaders)));
        $this->assertEquals(600, $newHeaders['x-reverse-proxy-ttl'][0]);
    }

    public function testDebug()
    {
        $subscriber = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->setConstructorArgs(array(null, 'X-Cache-Debug'))
            ->getMock();
        $event = $this->buildEvent();

        $subscriber->expects($this->once())->method('getOptions')->will($this->returnValue(array()));

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['x-cache-debug']), implode(',', array_keys($newHeaders)));
        $this->assertTrue(isset($newHeaders['x-cache-debug'][0]));
    }

    public function testConfigDefineRequestMatcherWithControllerName() {
        $extension = new FOSHttpCacheExtension();
        $container = new ContainerBuilder();

        $extension->load(array(
            array(
                'rules' => array(
                    array(
                        'match' => array(
                            'attributes' => array(
                                '_controller' => '^AcmeBundle:Default:index$',
                            ),
                        ),
                        'headers' => array(
                            'last_modified' => '-1 hour',
                        ),
                    ),
                ),
            ),
        ), $container);

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
        $subscriber = new CacheControlSubscriber();

        $headers = array('cache_control' => array(
            'max_age' => '900',
            's_maxage' => '300',
            'public' => true,
            'private' => false
        ));

        $subscriber->add(
            new RequestMatcher(null, null, null, null, array('_controller' => '^AcmeBundle:Default:index$')),
            array(),
            $headers
        );

        // Request with a matching controller name
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $request = new Request();
        $request->attributes->set('_controller', 'AcmeBundle:Default:index');
        $response = new Response();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $subscriber->onKernelResponse($event);
        $newHeaders = $response->headers->all();

        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);

        // Request with a non-matching controller name
        $request = new Request();
        $request->attributes->set('_controller', 'AcmeBundle:Default:notIndex');
        $response = new Response();
        $event = new FilterResponseEvent($kernel, $request, 'GET', $response);

        $subscriber->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertEquals('no-cache', $newHeaders['cache-control'][0]);
    }

    public function testUnlessRole()
    {
        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $subscriber = new CacheControlSubscriber($security);
        $subscriber->add(
            new RequestMatcher(),
            array(
                'unless_role' => 'ROLE_NO_CACHE',
            ),
            array(
                'cache_control' => array('public' => true),
            )
        );
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['cache-control']), implode(',', array_keys($newHeaders)));
        $this->assertEquals('public', $newHeaders['cache-control'][0]);

        // now with security saying we have the unless_role
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_NO_CACHE')
            ->will($this->returnValue(true))
        ;
        $response = new Response();
        $subscriber->onKernelResponse($event);

        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['cache-control']), implode(',', array_keys($newHeaders)));
        $this->assertNotContains('public', $newHeaders['cache-control'][0]);
    }

    public function testUnsafeMethod()
    {
        $subscriber = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->getMock()
        ;
        $subscriber->expects($this->never())
            ->method('getOptions')
        ;
        $event = $this->buildEvent('POST');

        $subscriber->onKernelResponse($event);
    }

    public function testMatchResponse()
    {
        $subscriber = new CacheControlSubscriber();
        $subscriber->add(
            new RequestMatcher(),
            array(
                'match_response' => 'response.getStatusCode() >= 300',
            ),
            array(
                'cache_control' => array('public' => true),
            )
        );
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);
        $response = $event->getResponse();
        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['cache-control']), implode(',', array_keys($newHeaders)));
        $this->assertEquals('no-cache', $newHeaders['cache-control'][0]);

        $response->setStatusCode(400);
        $subscriber->onKernelResponse($event);
        $newHeaders = $response->headers->all();

        $this->assertTrue(isset($newHeaders['cache-control']), implode(',', array_keys($newHeaders)));
        $this->assertEquals('public', $newHeaders['cache-control'][0]);
    }

    protected function buildEvent($method = 'GET')
    {
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $request->setMethod($method);

        return new FilterResponseEvent($kernel, $request, $method, $response);
    }

    /**
     * @param array $headers The headers to return in getOptions
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheControlSubscriber
     */
    protected function getCacheControl(array $headers)
    {
        $subscriber = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('getOptions'))
            ->getMock()
        ;

        $subscriber->expects($this->once())->method('getOptions')->will($this->returnValue($headers));

        return $subscriber;
    }
}
