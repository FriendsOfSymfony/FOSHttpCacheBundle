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
 * Class InvalidateRegexCommandTest
 */
class InvalidateRegexCommandTest extends CommandTestCase
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
            ->shouldReceive('invalidateRegex')->once()->with('/my.*/path')
            ->shouldReceive('flush')->once()->andReturn(1)
        ;

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:regex /my.*/path');
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
            ->shouldReceive('invalidateRegex')->once()->with('/my.*/path')
            ->shouldReceive('flush')->once()->andReturn(1)
        ;

        $output = $this->runCommand($client, 'fos:httpcache:invalidate:regex /my.*/path', OutputInterface::VERBOSITY_VERBOSE);

        $this->assertEquals("Sent 1 invalidation request(s)\n", $output);
    }
}
