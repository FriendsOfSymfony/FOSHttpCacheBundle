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

class CacheControlListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testIsCached()
    {
        $client = static::createClient();

        $client->request('GET', '/cached/42');
        $response = $client->getResponse();
        $this->assertEquals('public', $response->headers->get('Cache-Control'));
    }

    public function testNotCached()
    {
        $client = static::createClient();

        $client->request('GET', '/noncached');
        $response = $client->getResponse();
        // using contains because Symfony 3.2 add `private` when the cache is not public
        $this->assertContains('no-cache', $response->headers->get('Cache-Control'));
    }
}
