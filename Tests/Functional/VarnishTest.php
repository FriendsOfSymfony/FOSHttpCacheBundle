<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

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

    public function testPurgeContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $response = self::getResponse('/negotation.php', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getContentType());
        $this->assertHit(self::getResponse('/negotation.php', $json));

        $response = self::getResponse('/negotation.php', $html);
        $this->assertEquals('text/html', $response->getContentType());
        $this->assertMiss($response);
        $this->assertHit(self::getResponse('/negotation.php', $html));

        $this->varnish->purge('/negotation.php')->flush();
        $this->assertMiss(self::getResponse('/negotation.php', $json));
        $this->assertMiss(self::getResponse('/negotation.php', $html));
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

    public function testRefreshContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $this->varnish->refresh('/negotation.php', $json)->flush();

        $this->assertHit(self::getResponse('/negotation.php', $json));
        $this->assertMiss(self::getResponse('/negotation.php', $html));
    }
}