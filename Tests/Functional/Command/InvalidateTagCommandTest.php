<?php

namespace FOS\HttpCacheBundle\Tests\Functional\Command;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

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
