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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InvalidationListenerTest extends WebTestCase
{
    public function testInvalidateRoute()
    {
        $client = static::createClient();

        $mock = $this->createMock(CacheManager::class);
        $mock->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;
        $mock->expects($this->at(0))
            ->method('invalidateRoute')
            ->with('test_noncached')
        ;
        $mock->expects($this->at(1))
            ->method('invalidateRoute')
            ->with('test_cached', ['id' => 'myhardcodedid'])
        ;
        $mock->expects($this->at(2))
            ->method('invalidateRoute')
            ->with('tag_one', ['id' => 42])
        ;
        $mock->expects($this->once())
            ->method('flush')
            ->willReturn(3)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', '/invalidate/route/42');
    }

    /**
     * @dataProvider getStatusCodesThatTriggerInvalidation
     */
    public function testInvalidatePath($statusCode)
    {
        $client = static::createClient();

        $mock = $this->createMock(CacheManager::class);
        $mock->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;
        $mock->expects($this->at(0))
            ->method('invalidatePath')
            ->with('/cached')
        ;
        $mock->expects($this->at(1))
            ->method('invalidatePath')
            ->with(sprintf('/invalidate/path/%s', $statusCode))
        ;
        $mock->expects($this->once())
            ->method('flush')
            ->willReturn(2)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', sprintf('/invalidate/path/%s', $statusCode));
    }

    public function testErrorIsNotInvalidated()
    {
        $client = static::createClient();

        $mock = $this->createMock(CacheManager::class);
        $mock->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;
        $mock->expects($this->never())
            ->method('invalidateTags')
        ;
        $mock->expects($this->once())
            ->method('flush')
            ->willReturn(0)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', '/invalidate/error');
    }

    public function getStatusCodesThatTriggerInvalidation()
    {
        return [[200], [204], [302]];
    }
}
