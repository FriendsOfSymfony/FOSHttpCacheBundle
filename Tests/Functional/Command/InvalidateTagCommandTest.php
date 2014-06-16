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

use Symfony\Component\Console\Output\OutputInterface;

class InvalidateTagCommandTest extends CommandTestCase
{
    public function testExecute()
    {
        $client = self::createClient();
        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateTags')->once()->with(array('my-tag', 'other-tag'))
            ->shouldReceive('flush')->once()->andReturn(1)
        ;

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:tag my-tag other-tag');
        $this->assertEquals('', $output);
    }

    public function testExecuteVerbose()
    {
        $client = self::createClient();
        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('invalidateTags')->once()->with(array('my-tag', 'other-tag'))
            ->shouldReceive('flush')->once()->andReturn(1)
        ;

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:tag my-tag other-tag', OutputInterface::VERBOSITY_VERBOSE);

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }
}
