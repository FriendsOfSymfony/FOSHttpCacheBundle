<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\SymfonyCache;

use FOS\HttpCache\SymfonyCache\PurgeSubscriber;
use FOS\HttpCache\SymfonyCache\RefreshSubscriber;
use FOS\HttpCacheBundle\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EventDispatchingHttpCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return EventDispatchingHttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHttpCachePartialMock(array $mockedMethods = null)
    {
        $mock = $this
            ->getMockBuilder('\FOS\HttpCacheBundle\SymfonyCache\EventDispatchingHttpCache')
            ->setMethods($mockedMethods)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Force setting options property since we can't use original constructor.
        $options = array(
            'debug' => false,
            'default_ttl' => 0,
            'private_headers' => array('Authorization', 'Cookie'),
            'allow_reload' => false,
            'allow_revalidate' => false,
            'stale_while_revalidate' => 2,
            'stale_if_error' => 60,
        );

        $refHttpCache = new \ReflectionClass('Symfony\Component\HttpKernel\HttpCache\HttpCache');
        // Workaround for Symfony 2.3 where $options property is not defined.
        if (!$refHttpCache->hasProperty('options')) {
            $mock->options = $options;
        } else {
            $refOptions = $refHttpCache
                ->getProperty('options');
            $refOptions->setAccessible(true);
            $refOptions->setValue($mock, $options);
        }

        return $mock;
    }

    public function testCalled()
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(array('lookup'));
        $subscriber = new TestSubscriber($this, $httpCache, $request);
        $httpCache->addSubscriber($subscriber);
        $httpCache
            ->expects($this->any())
            ->method('lookup')
            ->with($request)
            ->will($this->returnValue($response))
        ;
        $httpCache->handle($request);

        $this->assertEquals(1, $subscriber->hits);
        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch));
    }

    public function testAbort()
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(array('lookup'));
        $subscriber = new TestSubscriber($this, $httpCache, $request, $response);
        $httpCache->addSubscriber($subscriber);
        $httpCache
            ->expects($this->never())
            ->method('lookup')
        ;
        $httpCache->handle($request);

        $this->assertEquals(1, $subscriber->hits);
        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch));
    }

    /**
     * @dataProvider configuredSubscribersProvider
     */
    public function testConfiguredSubscribers(array $options, array $expectedSubscribers)
    {
        $cacheKernel = $this->getHttpCachePartialMock(array('getOptions'));
        $cacheKernel
            ->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue($options))
        ;
        $refCache = new \ReflectionClass(get_class($cacheKernel));
        $refGetSubscribers = $refCache->getMethod('getDefaultSubscribers');
        $refGetSubscribers->setAccessible(true);

        $this->assertEquals($expectedSubscribers, $refGetSubscribers->invoke($cacheKernel));
    }

    public function configuredSubscribersProvider()
    {
        $all = array(new UserContextSubscriber(), new PurgeSubscriber(), new RefreshSubscriber());

        return array(
            array(array(), $all),
            array(
                array(
                    'fos_default_subscribers' => EventDispatchingHttpCache::SUBSCRIBER_ALL,
                ),
                $all,
            ),
            array(
                array(
                    'fos_default_subscribers' => EventDispatchingHttpCache::SUBSCRIBER_NONE,
                ),
                array(),
            ),
            array(
                array(
                    'fos_default_subscribers' => EventDispatchingHttpCache::SUBSCRIBER_USER_CONTEXT,
                ),
                array(new UserContextSubscriber()),
            ),
        );
    }
}

class TestSubscriber implements EventSubscriberInterface
{
    public $hits = 0;

    private $test;

    private $kernel;

    private $request;

    private $response;

    public function __construct($test, $kernel, $request, $response = null)
    {
        $this->test = $test;
        $this->kernel = $kernel;
        $this->request = $request;
        $this->response = $response;
    }

    public static function getSubscribedEvents()
    {
        return array(Events::PRE_HANDLE => 'preHandle');
    }

    public function preHandle(CacheEvent $event)
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->response) {
            $event->setResponse($this->response);
        }
        ++$this->hits;
    }
}
