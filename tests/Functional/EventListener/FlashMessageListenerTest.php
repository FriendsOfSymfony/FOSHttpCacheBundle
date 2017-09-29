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

class FlashMessageListenerTest extends WebTestCase
{
    public function testAnnotationTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/flash');
        $response = $client->getResponse();
        $this->assertEquals('flash', $response->getContent());
        $cookies = $client->getResponse()->headers->getCookies();
        $this->assertCount(1, $cookies);

        /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
        $cookie = $cookies[0];

        $this->assertEquals('/', $cookie->getPath());
        $this->assertEquals('mydomain.com', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals('flash_cookie_name', $cookie->getName());
        $this->assertJsonStringEqualsJsonString(json_encode(['notice' => ['Flash Message!']]), $cookie->getValue());
    }
}
