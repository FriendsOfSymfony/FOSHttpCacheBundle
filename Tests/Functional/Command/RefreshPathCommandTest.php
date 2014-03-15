<?php

namespace FOS\HttpCacheBundle\Tests\Functional\Command;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class RefreshPathCommandTest extends CommandTestCase
{
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
