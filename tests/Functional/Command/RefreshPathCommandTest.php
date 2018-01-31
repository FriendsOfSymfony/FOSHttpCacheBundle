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

class RefreshPathCommandTest extends CommandTestCase
{
    public function testExecuteVerbose()
    {
        $client = self::createClient();

        $mock = $this->createMock(CacheManager::class);
        $mock->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;
        $mock->expects($this->at(0))
            ->method('refreshPath')
            ->with('http://example.com/my/path', [])
        ;
        $mock->expects($this->at(1))
            ->method('refreshPath')
            ->with('http://example.com/other/path', [])
        ;
        $mock->expects($this->once())
            ->method('flush')
            ->willReturn(2)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:refresh:path http://example.com/my/path http://example.com/other/path');

        $this->assertEquals("Sent 2 invalidation request(s)\n", $output);
    }
}
