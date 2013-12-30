<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

use FOS\HttpCacheBundle\Invalidation\Varnish;


class VarnishTest extends FunctionalTestCase
{
    public function testBanAll()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->assertMiss(self::getResponse('/json.php'));
        $this->assertHit(self::getResponse('/json.php'));

        $this->varnish->banPath('.*')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertMiss(self::getResponse('/json.php'));
    }

    public function testBanContentType()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->assertMiss(self::getResponse('/json.php'));
        $this->assertHit(self::getResponse('/json.php'));

        $this->varnish->banPath('.*', 'text/html')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/json.php'));
    }

    public function testPurge()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->varnish->purge('/cache.php')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
    }

    public function testRefresh()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $response = self::getResponse('/cache.php');
        $this->assertHit($response);

        $this->varnish->refresh('/cache.php')->flush();

        sleep(1);
        $refreshed = self::getResponse('/cache.php');
        $this->assertGreaterThan((string) $response->getHeader('Age'), (string) $refreshed->getHeader('Age'));
    }
}