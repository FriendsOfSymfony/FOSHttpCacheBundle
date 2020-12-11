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

use FOS\HttpCacheBundle\CacheManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class InvalidationWithAttributesListenerTest
 * @package FOS\HttpCacheBundle\Tests\Functional\EventListener
 * @requires PHP 8.0
 */
class InvalidationWithAttributesListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testInvalidateRoute()
    {
        $client = static::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('invalidateRoute')
            ->once()
            ->with('test_noncached', [])
        ;
        $mock->shouldReceive('invalidateRoute')
            ->once()
            ->with('test_cached', ['id' => 'myhardcodedid'])
        ;
        $mock->shouldReceive('invalidateRoute')
            ->once()
            ->with('tag_one', ['id' => 42])
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(3)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', '/php8/invalidate/route/42');
    }

    /**
     * @dataProvider getStatusCodesThatTriggerInvalidation
     */
    public function testInvalidatePath($statusCode)
    {
        $client = static::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('invalidatePath')
            ->once()
            ->with('/php8/cached')
        ;
        $mock->shouldReceive('invalidatePath')
            ->once()
            ->with(sprintf('/php8/invalidate/path/%s', $statusCode))
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(2)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', sprintf('/php8/invalidate/path/%s', $statusCode));
    }

    public function testErrorIsNotInvalidated()
    {
        $client = static::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(0)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', '/php8/invalidate/error');
    }

    public function getStatusCodesThatTriggerInvalidation()
    {
        return [[200], [204], [302]];
    }
}
