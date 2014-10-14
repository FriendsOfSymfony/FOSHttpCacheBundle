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

use FOS\HttpCacheBundle\EventListener\CacheControlSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CacheControlSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultHeaders()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite' => false,
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
        $headers = array('overwrite' => false,
                         'cache_control' => array(
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
        $headers = array(
            'overwrite' => false,
            'cache_control' => array(
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            )
        );
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=300, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
    }

    public function testSetNoCacheHeaders()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite'     => false,
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

    public function testMergeHeaders()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite' => false,
            'cache_control' => array(
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            ),
            'vary' => array(
                'Cookie',
            ),
            'last_modified' => '2014-10-10 GMT',
        );
        $subscriber = $this->getCacheControl($headers);
        $response = $event->getResponse();
        $response->setPublic();
        $response->setCache(array('max_age' => 0));
        $response->headers->addCacheControlDirective('stale-if-error', 0);
        $response->setVary('Encoding');
        $response->setLastModified(new \DateTime('2013-09-09 GMT'));

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=0, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=0, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
        $this->assertEquals(array('Encoding', 'Cookie'), $newHeaders['vary']);
        $this->assertEquals('Mon, 09 Sep 2013 00:00:00 GMT', $newHeaders['last-modified'][0]);
    }

    public function testOverwriteHeaders()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite' => true,
            'cache_control' => array(
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            ),
            'vary' => array(
                'Cookie',
            ),
            'last_modified' => '2014-10-10 GMT',
        );
        $subscriber = $this->getCacheControl($headers);
        $response = $event->getResponse();
        $response->setPublic();
        $response->setCache(array('max_age' => 0));
        $response->headers->addCacheControlDirective('stale-if-error', 0);
        $response->setVary('Encoding');
        $response->setLastModified(new \DateTime('2013-09-09 GMT'));

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=300, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
        $this->assertEquals(array('Cookie'), $newHeaders['vary']);
        $this->assertEquals('Fri, 10 Oct 2014 00:00:00 GMT', $newHeaders['last-modified'][0]);
    }

    public function testMergePublicPrivate()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite' => false,
            'cache_control' => array(
                'private' => true,
        ));
        $subscriber = $this->getCacheControl($headers);
        $response = $event->getResponse();
        $response->setPublic();

        $subscriber->onKernelResponse($event);
        $newHeaders = $response->headers->all();

        $this->assertEquals('public', $newHeaders['cache-control'][0]);
    }

    /**
     * The no_cache header is never actually called as its already set.
     */
    public function testSetOnlyNoCacheHeader()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite' => false,
            'cache_control' => array(
                'no_cache' => true,
            ),
        );
        $subscriber = $this->getCacheControl($headers);

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('no-cache', $newHeaders['cache-control'][0]);
    }

    public function testSkip()
    {
        $event = $this->buildEvent();
        $subscriber = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('matchRule'))
            ->getMock()
        ;
        $subscriber->expects($this->never())
            ->method('matchRule')
        ;

        $subscriber->setSkip();
        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('no-cache', $newHeaders['cache-control'][0]);
    }

    public function testVary()
    {
        $event = $this->buildEvent();
        $headers = array(
            'overwrite' => false,
            'vary' => array(
                'Cookie',
                'Accept-Language',
                'Encoding',
            )
        );
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

    public function testDebugHeader()
    {
        $subscriber = \Mockery::mock('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber[matchRule]', array('X-Cache-Debug'))
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('matchRule')->once()->andReturn(false)
            ->getMock()
        ;
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['x-cache-debug']), implode(',', array_keys($newHeaders)));
        $this->assertTrue(isset($newHeaders['x-cache-debug'][0]));
    }

    public function testMatchRule()
    {
        $event = $this->buildEvent();
        $request = $event->getRequest();
        $response = $event->getResponse();
        $event2 = $this->buildEvent();
        $request2 = $event2->getRequest();
        $response2 = $event2->getResponse();

        $headers = array(
            'overwrite' => false,
            'cache_control' => array(
            'max_age' => '900',
            's_maxage' => '300',
            'public' => true,
            'private' => false
        ));

        $mockMatcher = \Mockery::mock('FOS\HttpCacheBundle\Http\RuleMatcherInterface')
            ->shouldReceive('matches')->once()->with($request, $response)->andReturn(true)
            ->shouldReceive('matches')->once()->with($request2, $response2)->andReturn(false)
            ->getMock()
        ;

        $subscriber = new CacheControlSubscriber();
        $subscriber->addRule(
            $mockMatcher,
            $headers
        );

        $subscriber->onKernelResponse($event);
        $newHeaders = $response->headers->all();
        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);

        $subscriber->onKernelResponse($event2);
        $newHeaders = $response2->headers->all();
        $this->assertEquals('no-cache', $newHeaders['cache-control'][0]);
    }

    /**
     * Unsafe methods should abort before even attempting to match rules.
     */
    public function testUnsafeMethod()
    {
        $subscriber = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('matchRule'))
            ->getMock()
        ;
        $subscriber->expects($this->never())
            ->method('matchRule')
        ;
        $event = $this->buildEvent('POST');

        $subscriber->onKernelResponse($event);
    }

    /**
     * Build the filter response event with a mock kernel and default request
     * and response objects.
     *
     * @param string $method
     *
     * @return FilterResponseEvent
     */
    protected function buildEvent($method = 'GET')
    {
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $response = new Response();
        $request = new Request();
        $request->setMethod($method);

        return new FilterResponseEvent($kernel, $request, $method, $response);
    }

    /**
     * We mock the matchRule method for tests about applying the rules.
     *
     * @param array $headers The headers to return in matchRule
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheControlSubscriber
     */
    protected function getCacheControl(array $headers)
    {
        $subscriber = $this->getMockBuilder('FOS\HttpCacheBundle\EventListener\CacheControlSubscriber')
            ->setMethods(array('matchRule'))
            ->getMock()
        ;

        $subscriber->expects($this->once())->method('matchRule')->will($this->returnValue($headers));

        return $subscriber;
    }
}
