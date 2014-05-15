<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Command;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RefreshPathCommandTest
 */
class RefreshPathCommandTest extends CommandTestCase
{
    /**
     * test execute
     */
    public function testExecute()
    {
        $client = self::createClient();
        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('refreshPath')->once()->with('http://example.com/my/path')
            ->shouldReceive('refreshPath')->once()->with('http://example.com/other/path')
            ->shouldReceive('flush')->once()->andReturn(2)
        ;

        $output = $this->runCommand($client, 'fos:httpcache:refresh:path http://example.com/my/path http://example.com/other/path');
        $this->assertEquals('', $output);
    }

    /**
     * test execute verbose
     */
    public function testExecuteVerbose()
    {
        $client = self::createClient();
        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('refreshPath')->once()->with('http://example.com/my/path')
            ->shouldReceive('refreshPath')->once()->with('http://example.com/other/path')
            ->shouldReceive('flush')->once()->andReturn(2)
        ;

        $output = $this->runCommand($client, 'fos:httpcache:refresh:path http://example.com/my/path http://example.com/other/path', OutputInterface::VERBOSITY_VERBOSE);

        $this->assertEquals("Sent 2 invalidation request(s)\n", $output);
    }
}
