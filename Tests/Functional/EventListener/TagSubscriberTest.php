<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TagSubscriberTest
 */
class TagSubscriberTest extends WebTestCase
{
    /**
     * test tags are set
     */
    public function testTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/test/list');
        $response = $client->getResponse();
        $this->assertEquals('all-items,item-123', $response->headers->get('X-Cache-Tags'));

        $client->request('GET', '/test/123');
        $response = $client->getResponse();
        $this->assertEquals('item-123', $response->headers->get('X-Cache-Tags'));
    }

    /**
     * test tags are invalidated
     */
    public function testTagsAreInvalidated()
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

    /**
     * test error is not invalidated
     */
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

    /**
     * tear down
     */
    protected function tearDown()
    {
        static::createClient()->getContainer()->unmock('fos_http_cache.cache_manager');
    }
}
