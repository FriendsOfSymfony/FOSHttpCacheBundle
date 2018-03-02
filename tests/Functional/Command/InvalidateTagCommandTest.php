<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Command;

use FOS\HttpCacheBundle\CacheManager;

class InvalidateTagCommandTest extends CommandTestCase
{
    public function testExecuteVerbose()
    {
        $client = self::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('invalidateTags')
            ->once()
            ->with(['my-tag', 'other-tag'])
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:tag my-tag other-tag');

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }
}
