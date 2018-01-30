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
use Symfony\Component\Console\Output\OutputInterface;

class InvalidateTagCommandTest extends CommandTestCase
{
    public function testExecuteVerbose()
    {
        $client = self::createClient();

        $mock = $client->getContainer()->get('fos_http_cache.cache_manager.prophecy');
        $mock->supports()->willReturn(true);
        $mock->invalidateTags(['my-tag', 'other-tag'])->willReturn(null);
        $mock->flush()->willReturn(1);

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:tag my-tag other-tag');

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }
}
