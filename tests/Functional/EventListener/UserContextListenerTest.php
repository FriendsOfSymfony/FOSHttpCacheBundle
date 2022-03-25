<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use FOS\HttpCacheBundle\Tests\Functional\SessionHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserContextListenerTest extends WebTestCase
{
    use SessionHelperTrait;

    public function testHashLookup()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);

        $this->callInRequestContext($client, [$this, 'setSessionId']);

        $client->request('GET', '/secured_area/_fos_user_context_hash', [], [], [
            'HTTP_ACCEPT' => 'application/vnd.fos.user-context-hash',
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->headers->has('X-User-Context-Hash'), 'X-User-Context-Hash header missing on the response');
        $this->assertEquals('5224d8f5b85429624e2160e538a3376a479ec87b89251b295c44ecbf7498ea3c', $response->headers->get('X-User-Context-Hash'), 'Not the expected context hash');
        $this->assertEquals('fos_http_cache_hashlookup-test', $response->headers->get('X-Cache-Tags'));
        $this->assertEquals('max-age=60, public', $response->headers->get('Cache-Control'));
    }

    public function testSessionCanBeCached()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);
        $client->request('GET', '/secured_area/cached_session', [], [], [
            'HTTP_X-User-Context-Hash' => '5224d8f5b85429624e2160e538a3376a479ec87b89251b295c44ecbf7498ea3c',
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful(), $response->getContent());
        $this->assertEquals('max-age=60, public', $response->headers->get('Cache-Control'));
    }

    public function setSessionId(RequestEvent $requestEvent): void
    {
        $session = $requestEvent->getRequest()->getSession();
        $session->setId('test');
        $session->start();
    }
}
