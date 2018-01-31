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

class InvalidateRegexCommandTest extends CommandTestCase
{
    public function testExecuteVerbose()
    {
        $client = self::createClient();

        $mock = $this->createMock(CacheManager::class);
        $mock->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;
        $mock->expects($this->once())
            ->method('invalidateRegex')
            ->with('/my.*/path')
        ;
        $mock->expects($this->once())
            ->method('flush')
            ->willReturn(1)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:regex /my.*/path');

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }
}
