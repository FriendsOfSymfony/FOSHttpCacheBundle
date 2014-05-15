<?php

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CacheControlSubscriberTest extends WebTestCase
{
    public function testIsCached()
    {
        $client = static::createClient();

        $client->request('GET', '/cachetest/cached');
        $response = $client->getResponse();
        $this->assertEquals('public', $response->headers->get('Cache-Control'));
    }

    public function testNotCached()
    {
        $client = static::createClient();

        $client->request('GET', '/cachetest/noncached');
        $response = $client->getResponse();
        $this->assertEquals('no-cache', $response->headers->get('Cache-Control'));
    }
}
