<?php

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TagSubscriberTest extends WebTestCase
{
    public function testAnnotationTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/test/list');
        $response = $client->getResponse();
        $this->assertEquals('all-items,item-123', $response->headers->get('X-Cache-Tags'));

        $client->request('GET', '/test/123');
        $response = $client->getResponse();
        $this->assertEquals('item-123', $response->headers->get('X-Cache-Tags'));
    }

    public function testAnnotationTagsAreInvalidated()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateTags')->once()->with(array('all-items'))
            ->shouldReceive('invalidateTags')->once()->with(array('item-123'))
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/test/123');
    }

    public function testErrorIsNotInvalidated()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateTags')->never()
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/test/error');
    }

    public function testConfigurationTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/cached');
        $response = $client->getResponse();
        $this->assertEquals('area', $response->headers->get('X-Cache-Tags'));
    }

    public function testConfigurationTagsAreInvalidated()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateTags')->once()->with(array('area'))
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/cached');
    }

    protected function tearDown()
    {
        static::createClient()->getContainer()->unmock('fos_http_cache.cache_manager');
    }
}
