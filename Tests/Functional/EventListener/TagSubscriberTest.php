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

class TagSubscriberTest extends WebTestCase
{
    public function testAnnotationTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/tag/list');
        $response = $client->getResponse();
        $this->assertEquals('all-items,item-123', $response->headers->get('X-Cache-Tags'));

        $client->request('GET', '/tag/123');
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

        $client->request('POST', '/tag/123');
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

        $client->request('POST', '/tag/error');
    }

    public function testConfigurationTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/cached/51');
        $response = $client->getResponse();
        $this->assertEquals('area,area-51', $response->headers->get('X-Cache-Tags'));
    }

    public function testConfigurationTagsAreInvalidated()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateTags')->once()->with(array('area', 'area-51'))
            ->shouldReceive('flush')->once()
        ;

        $client->request('PUT', '/cached/51');
    }

    protected function tearDown()
    {
        static::createClient()->getContainer()->unmock('fos_http_cache.cache_manager');
    }
}
