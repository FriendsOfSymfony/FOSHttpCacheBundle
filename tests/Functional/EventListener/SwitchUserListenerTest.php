<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use FOS\HttpCache\ProxyClient\Varnish;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;

class SwitchUserListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testSwitchUserCompatibility()
    {
        $client = static::createClient();
        $session = $client->getContainer()->get('session');
        $this->loginAsAdmin($client, $session);

        $client->request('GET', '/secured_area/switch_user?_switch_user=user');
        $client->request('GET', '/secured_area/switch_user');
        $this->assertSame('user', substr($client->getResponse()->getContent(), 0, 2000));

        $client->request('GET', '/secured_area/switch_user?_switch_user=_exit');
        $client->request('GET', '/secured_area/switch_user');
        $this->assertSame('admin', substr($client->getResponse()->getContent(), 0, 2000));
    }

    public function testInvalidateContext()
    {
        $client = static::createClient();
        $session = $client->getContainer()->get('session');
        $this->loginAsAdmin($client, $session);

        $mock = \Mockery::mock(Varnish::class);
        $mock->shouldReceive('invalidateTags')
            ->once()
            ->with(['fos_http_cache_hashlookup-test']);

        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1);

        $client->getContainer()->set('fos_http_cache.proxy_client.varnish', $mock);
        $client->request('GET', '/secured_area/switch_user?_switch_user=user');
    }

    private function loginAsAdmin(Client $client, Session $session, $firewallName = 'secured_area', $sessionId = 'test')
    {
        $token = new UsernamePasswordToken(new User('admin', 'admin'), null, $firewallName, ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']);

        $session->setId($sessionId);
        $session->set(sprintf('_security_%s', $firewallName), serialize($token));
        $session->save();

        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
