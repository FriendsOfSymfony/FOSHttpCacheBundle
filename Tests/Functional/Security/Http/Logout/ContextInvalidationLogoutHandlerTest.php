<?php

namespace FOS\HttpCacheBundle\Tests\Functional\Security\Http\Logout;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class ContextInvalidationLogoutHandlerTest extends WebTestCase
{
    public function testLogout()
    {
        $client = static::createClient();
        $client->getContainer()->mock(
            'fos_http_cache.proxy_client.varnish',
            '\FOS\HttpCache\ProxyClient\Varnish'
        )
            ->shouldReceive('ban')->once()->with(array('accept'=>'application/vnd.fos.user-context-hash', 'Cookie' => '.*test.*'))
            ->shouldReceive('ban')->once()->with(array('accept'=>'application/vnd.fos.user-context-hash', 'Authorization' => '.*test.*'))
            ->shouldReceive('flush')->once()
        ;

        $client->getCookieJar()->set(new Cookie('TESTSESSID', 'test'));
        $client->request('GET', '/secured_area/logout');
    }
}
