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

use FOS\HttpCacheBundle\EventListener\CacheControlListener;
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CacheControlListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDefaultHeaders()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'last_modified' => '13.07.2003',
            'cache_control' => [
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
            ],
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);
        $this->assertArrayHasKey('last-modified', $newHeaders, implode(',', array_keys($newHeaders)));
        $this->assertEquals(strtotime('13.07.2003'), strtotime($newHeaders['last-modified'][0]));
    }

    public function testEtagStrong()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'etag' => 'strong',
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('"d41d8cd98f00b204e9800998ecf8427e"', $newHeaders['etag'][0]);
    }

    public function testEtagWeak()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'etag' => 'weak',
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('W/"d41d8cd98f00b204e9800998ecf8427e"', $newHeaders['etag'][0]);
    }

    public function testExtraHeaders()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            ],
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('must-revalidate, no-transform, proxy-revalidate, stale-if-error=300, stale-while-revalidate=400, private', $newHeaders['cache-control'][0]);
    }

    public function testCompoundHeaders()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            ],
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=300, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
    }

    public function testSetNoCacheHeaders()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'max_age' => '0',
                's_maxage' => '0',
                'private' => true,
                'no_cache' => true,
                'no_store' => true,
                'must_revalidate' => true,
            ],
            'last_modified' => '13.07.2003',
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private, s-maxage=0', $newHeaders['cache-control'][0]);
    }

    public function testMergeHeaders()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            ],
            'vary' => [
                'Cookie',
            ],
            'etag' => true,
            'last_modified' => '2014-10-10 GMT',
        ];
        $listener = $this->getCacheControl($headers);
        $response = $event->getResponse();
        $response->setPublic();
        $response->setCache(['max_age' => 0]);
        $response->headers->addCacheControlDirective('stale-if-error', 0);
        $response->setVary('Encoding');
        $response->setEtag('foo');
        $response->setLastModified(new \DateTime('2013-09-09 GMT'));

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=0, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=0, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
        $this->assertEquals(['Encoding', 'Cookie'], $newHeaders['vary']);
        $this->assertEquals('"foo"', $newHeaders['etag'][0]);
        $this->assertEquals('Mon, 09 Sep 2013 00:00:00 GMT', $newHeaders['last-modified'][0]);
    }

    public function testOverwriteHeaders()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => true,
            'cache_control' => [
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_transform' => true,
                'stale_if_error' => '300',
                'stale_while_revalidate' => '400',
            ],
            'vary' => [
                'Cookie',
            ],
            'etag' => true,
            'last_modified' => '2014-10-10 GMT',
        ];
        $listener = $this->getCacheControl($headers);
        $response = $event->getResponse();
        $response->setPublic();
        $response->setCache(['max_age' => 0]);
        $response->headers->addCacheControlDirective('stale-if-error', 0);
        $response->setVary('Encoding');
        $response->setEtag('foo');
        $response->setLastModified(new \DateTime('2013-09-09 GMT'));

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertEquals('max-age=900, must-revalidate, no-transform, proxy-revalidate, public, s-maxage=300, stale-if-error=300, stale-while-revalidate=400', $newHeaders['cache-control'][0]);
        $this->assertEquals(['Cookie'], $newHeaders['vary']);
        $this->assertEquals('"'.md5('').'"', $response->getEtag());
        $this->assertEquals('Fri, 10 Oct 2014 00:00:00 GMT', $newHeaders['last-modified'][0]);
    }

    public function testMergePublicPrivate()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'private' => true,
            ],
        ];
        $listener = $this->getCacheControl($headers);
        $response = $event->getResponse();
        $response->setPublic();

        $listener->onKernelResponse($event);
        $newHeaders = $response->headers->all();

        $this->assertEquals('public', $newHeaders['cache-control'][0]);
    }

    /**
     * The no_cache header is never actually called as its already set.
     */
    public function testSetOnlyNoCacheHeader()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'no_cache' => true,
            ],
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertContains('no-cache', $newHeaders['cache-control'][0]);
    }

    public function testSetOnlyNoStoreHeader()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'no_store' => true,
            ],
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertContains('no-store', $newHeaders['cache-control'][0]);
    }

    public function testSkip()
    {
        $event = $this->buildEvent();
        $listener = new CacheControlListener();
        $matcher = \Mockery::mock(RuleMatcherInterface::class)
            ->shouldReceive('matches')->never()
            ->getMock();
        $listener->addRule($matcher);

        $listener->setSkip();
        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertContains('no-cache', $newHeaders['cache-control'][0]);
    }

    public function testVary()
    {
        $event = $this->buildEvent();
        $headers = [
            'overwrite' => false,
            'vary' => [
                'Cookie',
                'Accept-Language',
                'Encoding',
            ],
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['vary']), implode(',', array_keys($newHeaders)));
        $this->assertEquals($headers['vary'], $newHeaders['vary']);
    }

    public function testReverseProxyTtl()
    {
        $event = $this->buildEvent();
        $headers = [
            'reverse_proxy_ttl' => 600,
        ];
        $listener = $this->getCacheControl($headers);

        $listener->onKernelResponse($event);
        $newHeaders = $event->getResponse()->headers->all();

        $this->assertTrue(isset($newHeaders['x-reverse-proxy-ttl']), implode(',', array_keys($newHeaders)));
        $this->assertEquals(600, $newHeaders['x-reverse-proxy-ttl'][0]);
    }

    public function testDebugHeader()
    {
        $listener = new CacheControlListener('X-Cache-Debug');
        $matcher = \Mockery::mock(RuleMatcherInterface::class)
            ->shouldReceive('matches')->once()->andReturn(false)
            ->getMock();
        $listener->addRule($matcher);
        $event = $this->buildEvent();

        $listener->onKernelResponse($event);
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

        $headers = [
            'overwrite' => false,
            'cache_control' => [
                'max_age' => '900',
                's_maxage' => '300',
                'public' => true,
                'private' => false,
            ],
        ];

        $mockMatcher = \Mockery::mock(RuleMatcherInterface::class)
            ->shouldReceive('matches')->once()->with($request, $response)->andReturn(true)
            ->shouldReceive('matches')->once()->with($request2, $response2)->andReturn(false)
            ->getMock()
        ;

        $listener = new CacheControlListener();
        $listener->addRule(
            $mockMatcher,
            $headers
        );

        $listener->onKernelResponse($event);
        $newHeaders = $response->headers->all();
        $this->assertEquals('max-age=900, public, s-maxage=300', $newHeaders['cache-control'][0]);

        $listener->onKernelResponse($event2);
        $newHeaders = $response2->headers->all();
        $this->assertContains('no-cache', $newHeaders['cache-control'][0]);
    }

    /**
     * Unsafe methods should abort before even attempting to match rules.
     */
    public function testUnsafeMethod()
    {
        /** @var CacheControlListener|\PHPUnit_Framework_MockObject_MockObject $listener */
        $listener = $this->getMockBuilder(CacheControlListener::class)
            ->setMethods(['matchRule'])
            ->getMock()
        ;
        $listener->expects($this->never())
            ->method('matchRule')
        ;
        $event = $this->buildEvent('POST');

        $listener->onKernelResponse($event);
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
        /** @var HttpKernelInterface $kernel */
        $kernel = \Mockery::mock(HttpKernelInterface::class);
        $response = new Response();
        $request = new Request();
        $request->setMethod($method);

        return new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
    }

    /**
     * We mock a rule matcher for tests about applying the rules.
     *
     * @param array $headers The headers to return from the matcher
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheControlListener
     */
    protected function getCacheControl(array $headers)
    {
        $listener = new CacheControlListener();

        $matcher = \Mockery::mock(RuleMatcherInterface::class)
            ->shouldReceive(['matches' => true])
            ->getMock();
        $listener->addRule($matcher, $headers);

        return $listener;
    }
}
