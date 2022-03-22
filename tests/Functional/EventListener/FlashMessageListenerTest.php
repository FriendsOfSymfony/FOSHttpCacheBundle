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
use Symfony\Component\HttpKernel\Kernel;

class FlashMessageListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testFlashMessageCookieIsSet()
    {
        $client = static::createClient();

        $client->request('GET', '/flash');
        if (Kernel::MAJOR_VERSION < 6) {
            $session = static::$kernel->getContainer()->get('session');
            $this->assertFalse($session->isStarted());
        }
        $response = $client->getResponse();
        $this->assertEquals('flash', $response->getContent());
        $cookies = $response->headers->getCookies();
        $this->assertGreaterThanOrEqual(1, $cookies, implode(',', $cookies));

        $found = false;
        foreach ($cookies as $cookie) {
            if ('flash_cookie_name' !== $cookie->getName()) {
                continue;
            }

            $this->assertEquals('/', $cookie->getPath());
            $this->assertNull($cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertJsonStringEqualsJsonString(json_encode(['notice' => ['Flash Message!']]), $cookie->getValue());
            $found = true;
        }

        $this->assertTrue($found, 'Cookie "flash_cookie_name" not found in response cookies');
    }

    public function testFlashMessageCookieIsSetOnRedirect()
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->setMaxRedirects(2);

        $client->request('GET', '/flash-redirect');
        if (Kernel::MAJOR_VERSION < 6) {
            $session = static::$kernel->getContainer()->get('session');
            $this->assertFalse($session->isStarted());
        }
        $response = $client->getResponse();
        $cookies = $response->headers->getCookies();
        $this->assertGreaterThanOrEqual(1, $cookies, implode(',', $cookies));

        $found = false;
        foreach ($cookies as $cookie) {
            if ('flash_cookie_name' !== $cookie->getName()) {
                continue;
            }

            $this->assertEquals('/', $cookie->getPath());
            $this->assertNull($cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertJsonStringEqualsJsonString(json_encode(['notice' => ['Flash Message!', 'Flash Message!']]), $cookie->getValue());
            $found = true;
        }

        $this->assertTrue($found, 'Cookie "flash_cookie_name" not found in response cookies');
    }
}
