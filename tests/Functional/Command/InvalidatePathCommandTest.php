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

class InvalidatePathCommandTest extends CommandTestCase
{
    public function testExecuteVerbose()
    {
        $client = self::createClient();
        $mock = $client->getContainer()->get('fos_http_cache.cache_manager.prophecy');
        $mock->supports()->willReturn(true);
        $mock->invalidatePath('http://example.com/my/path')->willReturn(null);
        $mock->invalidatePath('http://example.com/other/path')->willReturn(null);
        $mock->flush()->willReturn(2);

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:path http://example.com/my/path http://example.com/other/path');

        $this->assertEquals("Sent 2 invalidation request(s)\n", $output);
    }
}
