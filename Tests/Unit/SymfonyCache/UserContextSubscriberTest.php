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

use FOS\HttpCacheBundle\SymfonyCache\CacheEvent;
use FOS\HttpCacheBundle\SymfonyCache\UserContextSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

class UserContextSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserContextSubscriber
     */
    private $userContextSubscriber;

    /**
     * @var HttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->userContextSubscriber = new UserContextSubscriber();
        $this->kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\HttpCache\HttpCache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testGenerateUserHashNotAllowed()
    {
        $request = new Request();
        $request->headers->set('accept', UserContextSubscriber::USER_HASH_ACCEPT_HEADER);
        $event = new CacheEvent($this->kernel, $request);

        $this->userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPassingUserHashNotAllowed()
    {
        $request = new Request();
        $request->headers->set(UserContextSubscriber::USER_HASH_HEADER, 'foo');
        $event = new CacheEvent($this->kernel, $request);

        $this->userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUserHashAnonymous()
    {
        $request = new Request();
        $catch = true;
        $subResponse = new Response();

        $event = new CacheEvent($this->kernel, $request);

        $this->userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has(UserContextSubscriber::USER_HASH_HEADER));
        $this->assertSame(UserContextSubscriber::ANONYMOUS_HASH, $request->headers->get(UserContextSubscriber::USER_HASH_HEADER));
    }

    public function testUserHashUserWithSession()
    {
        $catch = true;
        $sessionId1 = 'my_session_id';
        $sessionId2 = 'another_session_id';
        $cookies = array(
            'PHPSESSID' => $sessionId1,
            'PHPSESSIDsdiuhsdf4535d4f' => $sessionId2,
            'foo' => 'bar'
        );
        $cookieString = "PHPSESSID=$sessionId1; foo=bar; PHPSESSIDsdiuhsdf4535d4f=$sessionId2";
        $request = Request::create('/foo', 'GET', array(), $cookies, array(), array('Cookie' => $cookieString));

        $hashRequest = Request::create(UserContextSubscriber::USER_HASH_URI, UserContextSubscriber::USER_HASH_METHOD, array(), array(), array(), $request->server->all());
        $hashRequest->attributes->set('internalRequest', true);
        $hashRequest->headers->set('Accept', UserContextSubscriber::USER_HASH_ACCEPT_HEADER);
        $hashRequest->headers->set('Cookie', "PHPSESSID=$sessionId1; PHPSESSIDsdiuhsdf4535d4f=$sessionId2");
        $hashRequest->cookies->set('PHPSESSID', $sessionId1);
        $hashRequest->cookies->set('PHPSESSIDsdiuhsdf4535d4f', $sessionId2);
        // Ensure request properties have been filled up.
        $hashRequest->getPathInfo();
        $hashRequest->getMethod();

        $expectedContextHash = 'my_generated_hash';
        // Just avoid the response to modify the request object, otherwise it's impossible to test objects equality.
        /** @var \Symfony\Component\HttpFoundation\Response|\PHPUnit_Framework_MockObject_MockObject $hashResponse */
        $hashResponse = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Response')
            ->setMethods(array('prepare'))
            ->getMock();
        $hashResponse->headers->set(UserContextSubscriber::USER_HASH_HEADER, $expectedContextHash );

        $that = $this;
        $this->kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(function (Request $request) use ($that, $hashRequest) {
                    // we need to call some methods to get the internal fields initialized
                    $request->getMethod();
                    $request->getPathInfo();
                    $that->assertEquals($hashRequest, $request);

                    return true;
                }),
                $catch
            )
            ->will($this->returnValue($hashResponse));

        $event = new CacheEvent($this->kernel, $request);

        $this->userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has(UserContextSubscriber::USER_HASH_HEADER));
        $this->assertSame($expectedContextHash, $request->headers->get(UserContextSubscriber::USER_HASH_HEADER));
    }
}
