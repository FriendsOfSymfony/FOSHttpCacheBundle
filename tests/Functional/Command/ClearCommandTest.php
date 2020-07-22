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

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCacheBundle\CacheManager;

class ClearCommandTest extends CommandTestCase
{
    public function testExecuteClearVerbose()
    {
        $client = self::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->with(CacheInvalidator::CLEAR)
            ->andReturnTrue();
        ;
        $mock->shouldReceive('clearCache')
            ->once()
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:clear');

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }

    public function testExecuteBanVerbose()
    {
        $client = self::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->with(CacheInvalidator::CLEAR)
            ->andReturnFalse();
        $mock->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->andReturnTrue();
        ;
        $mock->shouldReceive('invalidateRegex')
            ->with('.*')
            ->once()
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:clear');

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }

    public function testExecuteErrorVerbose()
    {
        $client = self::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->with(CacheInvalidator::CLEAR)
            ->andReturnFalse();
        $mock->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->andReturnFalse();
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(0)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:clear', 1);

        $this->assertStringContainsString("The configured http cache does not support \"clear\" or \"invalidate\".", $output);
    }
}
