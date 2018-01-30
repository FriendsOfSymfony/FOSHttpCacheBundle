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
use Symfony\Component\HttpFoundation\Cookie;

class FlashMessageListenerTest extends WebTestCase
{
    public function testFlashMessageCookieIsSet()
    {
        $client = static::createClient();
        $session = static::$kernel->getContainer()->get('session');

        $client->request('GET', '/flash');
        $this->assertFalse($session->isStarted());
        $response = $client->getResponse();
        $this->assertEquals('flash', $response->getContent());
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies, implode(',', $cookies));

        /** @var Cookie $cookie */
        $cookie = $cookies[0];

        $this->assertEquals('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals('flash_cookie_name', $cookie->getName());
        $this->assertJsonStringEqualsJsonString(json_encode(['notice' => ['Flash Message!']]), $cookie->getValue());
    }
}
