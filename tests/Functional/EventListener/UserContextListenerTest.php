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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserContextListenerTest extends WebTestCase
{
    /**
     * @dataProvider userHashDataProvider
     */
    public function testHashLookup(string $username, string $hash)
    {
        $client = static::createClient([], $username ? [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $username,
        ] : []);
        /** @var SessionInterface $session */
        $session = $client->getContainer()->get('session');
        $session->setId('test');
        $session->start();

        $client->request('GET', '/secured_area/_fos_user_context_hash', [], [], [
            'HTTP_ACCEPT' => 'application/vnd.fos.user-context-hash',
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->headers->has('X-User-Context-Hash'), 'X-User-Context-Hash header missing on the response');
        $this->assertEquals($hash, $response->headers->get('X-User-Context-Hash'), 'Not the expected context hash');
        $this->assertEquals('fos_http_cache_hashlookup-test', $response->headers->get('X-Cache-Tags'));
        $this->assertEquals('max-age=60, public', $response->headers->get('Cache-Control'));
    }

    /**
     * @dataProvider userHashDataProvider
     */
    public function testSessionCanBeCached(string $username, string $hash)
    {
        $client = static::createClient([], $username ? [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $username,
        ] : []);
        $client->request('GET', '/secured_area/cached_session', [], [], [
            'HTTP_X-User-Context-Hash' => $hash,
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('max-age=60, public', $response->headers->get('Cache-Control'));
    }

    public function userHashDataProvider()
    {
        yield ['', '5224d8f5b85429624e2160e538a3376a479ec87b89251b295c44ecbf7498ea3c'];
        yield ['user', '14cea38921d7f2284a52ac67eafb9ed5d30bed84684711591747d9110cae8be9'];
        yield ['admin', '0878038c198f135419a0ac4df7ecc61b8113b6ef681711f1a5e4aff72616d601'];
    }
}
