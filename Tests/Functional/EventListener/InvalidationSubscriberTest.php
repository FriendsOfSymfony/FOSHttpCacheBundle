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

class InvalidationSubscriberTest extends WebTestCase
{
    public function testInvalidateRoute()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateRoute')->once()->with('test_noncached', array())
            ->shouldReceive('invalidateRoute')->once()->with('test_cached', array('id' => 'myhardcodedid'))
            ->shouldReceive('invalidateRoute')->once()->with('tag_one', array('id' => '42'))
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/invalidate/route/42');
    }

    public function testInvalidatePath()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidatePath')->once()->with('/cached')
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/invalidate/path');
    }

    public function testErrorIsNotInvalidated()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidatePath')->never()
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/invalidate/error');
    }

    protected function tearDown()
    {
        static::createClient()->getContainer()->unmock('fos_http_cache.cache_manager');
    }
}
