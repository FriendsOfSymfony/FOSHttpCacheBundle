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

class InvalidateDomainCommandTest extends CommandTestCase
{
    public function testExecuteVerbose()
    {
        $client = self::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('invalidateDomain')
            ->once()
            ->with('localhost')
        ;
        $mock->shouldReceive('invalidateDomain')
            ->once()
            ->with('example.localhost')
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(2)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:domain localhost example.localhost');

        $this->assertEquals("Sent 2 invalidation request(s)\n", $output);
    }
}
