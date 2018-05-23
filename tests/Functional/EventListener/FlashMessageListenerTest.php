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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;

class FlashMessageListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testFlashMessageCookieIsSet()
    {
        $client = static::createClient();
        $session = static::$kernel->getContainer()->get('session');

        $client->request('GET', '/flash');
        $this->assertFalse($session->isStarted());
        $response = $client->getResponse();
        $this->assertEquals('flash', $response->getContent());
        $cookies = $response->headers->getCookies();
        $this->assertGreaterThanOrEqual(1, $cookies, implode(',', $cookies));

        $found = false;
        foreach ($cookies as $cookie) {
            /** @var Cookie $cookie */
            if ('flash_cookie_name' !== $cookie->getName()) {
                continue;
            }

            $this->assertEquals('/', $cookie->getPath());
            $this->assertNull($cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertJsonStringEqualsJsonString(json_encode(['notice' => ['Flash Message!']]), $cookie->getValue());
            $found = true;
        }

        if (!$found) {
            $this->fail('Cookie flash_cookie_name not found in the cookie response header: '.implode(',', $cookies));
        }
    }
}
