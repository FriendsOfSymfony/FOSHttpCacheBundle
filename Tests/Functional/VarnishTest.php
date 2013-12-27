<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

use FOS\HttpCacheBundle\Invalidation\Varnish;


class VarnishTest extends FunctionalTestCase
{
    /**
     * @var Varnish
     */
    protected $varnish;

    public function setUp()
    {
        $this->varnish = new Varnish(array('http://127.0.0.1:6081'), 'localhost:6081');

        // After each test, restart Varnish to clear caches
        exec('sudo service varnish restart');
    }

    public function testBanAll()
    {
        $this->assertMiss(self::getResponse('/cache.php')->send());
        $this->assertHit(self::getResponse('/cache.php')->send());

        $this->assertMiss(self::getResponse('/json.php')->send());
        $this->assertHit(self::getResponse('/json.php')->send());

        $this->varnish->ban('.*')->flush();
        $this->assertMiss(self::getResponse('/cache.php')->send());
        $this->assertMiss(self::getResponse('/json.php')->send());
    }

    public function testBanContentType()
    {
        $this->assertMiss(self::getResponse('/cache.php')->send());
        $this->assertHit(self::getResponse('/cache.php')->send());

        $this->assertMiss(self::getResponse('/json.php')->send());
        $this->assertHit(self::getResponse('/json.php')->send());

        $this->varnish->ban('.*', 'text/html')->flush();
        $this->assertMiss(self::getResponse('/cache.php')->send());
        $this->assertHit(self::getResponse('/json.php')->send());
    }

    public function testPurge()
    {
        $this->assertMiss(self::getResponse('/cache.php')->send());
        $this->assertHit(self::getResponse('/cache.php')->send());

        $this->varnish->purge('/cache.php')->flush();
        $this->assertMiss(self::getResponse('/cache.php')->send());
    }

    public function testRefresh()
    {
        $this->assertMiss(self::getResponse('/cache.php')->send());
        $response = self::getResponse('/cache.php')->send();
        $this->assertHit($response);

        $this->varnish->refresh('/cache.php')->flush();

        sleep(1);
        $refreshed = self::getResponse('/cache.php')->send();
        $this->assertGreaterThan((string) $response->getHeader('Age'), (string) $refreshed->getHeader('Age'));
    }
}