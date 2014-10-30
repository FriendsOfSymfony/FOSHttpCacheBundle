<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit;

use FOS\HttpCacheBundle\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \FOS\HttpCacheBundle\HttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHttpCachePartialMock(array $mockedMethods = null)
    {
        $mock = $this->getMockBuilder('\FOS\HttpCacheBundle\HttpCache')
                     ->setMethods( $mockedMethods )
                     ->disableOriginalConstructor()
                     ->getMock();

        // Force setting options property since we can't use original constructor.
        $options = array(
            'debug' => false,
            'default_ttl' => 0,
            'private_headers' => array( 'Authorization', 'Cookie' ),
            'allow_reload' => false,
            'allow_revalidate' => false,
            'stale_while_revalidate' => 2,
            'stale_if_error' => 60,
        );

        $refMock = new \ReflectionObject($mock);
        $refHttpCache = $refMock
            // \FOS\HttpCacheBundle\HttpCache
            ->getParentClass()
            // \Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache
            ->getParentClass()
            // \Symfony\Component\HttpKernel\HttpCache\HttpCache
            ->getParentClass();
        // Workaround for Symfony 2.3 where $options property is not defined.
        if (!$refHttpCache->hasProperty('options')) {
            $mock->options = $options;
        } else {
            $refOptions = $refHttpCache
                ->getProperty('options');
            $refOptions->setAccessible(true);
            $refOptions->setValue($mock, $options );
        }

        return $mock;
    }

    public function testGenerateUserHashNotAllowed()
    {
        $request = new Request();
        $request->headers->set('accept', HttpCache::USER_HASH_ACCEPT_HEADER);
        $httpCache = $this->getHttpCachePartialMock();
        $response = $httpCache->handle($request);
        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPassingUserHashNotAllowed()
    {
        $request = new Request();
        $request->headers->set(HttpCache::USER_HASH_HEADER, 'foo');
        $httpCache = $this->getHttpCachePartialMock();
        $response = $httpCache->handle($request);
        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUserHashAnonymous()
    {
        $request = new Request();
        $catch = true;

        $httpCache = $this->getHttpCachePartialMock(array('lookup'));
        $response = new Response();
        $httpCache
            ->expects($this->once())
            ->method('lookup')
            ->with($request, $catch)
            ->will($this->returnValue($response));

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch));
        $this->assertTrue($request->headers->has(HttpCache::USER_HASH_HEADER));
        $this->assertSame(HttpCache::ANONYMOUS_HASH, $request->headers->get(HttpCache::USER_HASH_HEADER));
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
        $response = new Response();

        $hashRequest = Request::create(HttpCache::USER_HASH_URI, HttpCache::USER_HASH_METHOD, array(), array(), array(), $request->server->all());
        $hashRequest->attributes->set('internalRequest', true);
        $hashRequest->headers->set('Accept', HttpCache::USER_HASH_ACCEPT_HEADER);
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
        $hashResponse->headers->set(HttpCache::USER_HASH_HEADER, $expectedContextHash );

        $httpCache = $this->getHttpCachePartialMock(array('lookup'));
        $httpCache
            ->expects($this->at(0))
            ->method('lookup')
            ->with($hashRequest, $catch)
            ->will($this->returnValue($hashResponse));
        $httpCache
            ->expects($this->at(1))
            ->method('lookup')
            ->with($request)
            ->will($this->returnValue($response));

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch));
        $this->assertTrue($request->headers->has(HttpCache::USER_HASH_HEADER));
        $this->assertSame($expectedContextHash, $request->headers->get(HttpCache::USER_HASH_HEADER));
    }
}
