<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Security\Http\Logout;

use FOS\HttpCache\ProxyClient\Varnish;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ContextInvalidationLogoutHandlerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testLogout()
    {
        $client = static::createClient();
        $session = $client->getContainer()->get('session');

        $token = new UsernamePasswordToken('user', null, 'secured_area', ['ROLE_USER']);
        $session->setId('test');
        $session->set('_security_secured_area', serialize($token));
        $session->save();

        $mock = \Mockery::mock(Varnish::class);
        $mock->shouldReceive('invalidateTags')
            ->once()
            ->with(['fos_http_cache_hashlookup-test'])
        ;
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1)
        ;

        $client->getContainer()->set('fos_http_cache.proxy_client.varnish', $mock);

        $client->getCookieJar()->set(new Cookie($session->getName(), 'test'));
        $client->request('GET', '/secured_area/logout');

        $this->assertEquals(302, $client->getResponse()->getStatusCode(), substr($client->getResponse()->getContent(), 0, 2000));
    }
}
