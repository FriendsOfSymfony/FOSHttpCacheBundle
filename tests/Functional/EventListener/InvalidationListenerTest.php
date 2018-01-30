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

        $mock = $client->getContainer()->get('fos_http_cache.cache_manager.prophecy');
        $mock->supports()->willReturn(true);
        $mock->invalidateRoute('test_noncached', [])->willReturn(null);
        $mock->invalidateRoute('test_cached', ['id' => 'myhardcodedid'])->willReturn(null);
        $mock->invalidateRoute('tag_one', ['id' => 42])->willReturn(null);
        $mock->flush()->willReturn(3);

        $client->request('POST', '/invalidate/route/42');
    }

    /**
     * @dataProvider getStatusCodesThatTriggerInvalidation
     */
    public function testInvalidatePath($statusCode)
    {
        $client = static::createClient();

        $mock = $client->getContainer()->get('fos_http_cache.cache_manager.prophecy');
        $mock->supports()->willReturn(true);
        $mock->invalidatePath('/cached')->willReturn(null);
        $mock->invalidatePath(sprintf('/invalidate/path/%s', $statusCode))->willReturn(null);
        $mock->flush()->willReturn(2);

        $client->request('POST', sprintf('/invalidate/path/%s', $statusCode));
    }

    public function testErrorIsNotInvalidated()
    {
        $client = static::createClient();

        $mock = $client->getContainer()->get('fos_http_cache.cache_manager.prophecy');
        $mock->supports()->willReturn(true);
        $mock->flush()->willReturn(0);

        $client->request('POST', '/invalidate/error');
    }

    public function getStatusCodesThatTriggerInvalidation()
    {
        return [[200], [204], [302]];
    }
}
