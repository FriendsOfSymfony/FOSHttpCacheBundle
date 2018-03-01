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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ContextInvalidationLogoutHandlerTest extends WebTestCase
{
    public function testLogout()
    {
        $client = static::createClient();
        $session = $client->getContainer()->get('session');

        $token = new UsernamePasswordToken('user', null, 'secured_area', ['ROLE_USER']);
        $session->setId('test');
        $session->set('_security_secured_area', serialize($token));
        $session->save();

        $mock = $this->createMock(Varnish::class);
        $mock->expects($this->at(0))
            ->method('ban')
            ->with([
                'accept' => 'application/vnd.fos.user-context-hash',
                'Cookie' => '.*test.*',
            ])
        ;
        $mock->expects($this->at(1))
            ->method('ban')
            ->with([
                'accept' => 'application/vnd.fos.user-context-hash',
                'Authorization' => '.*test.*',
            ])
        ;
        $mock->expects($this->atLeast(1))
            ->method('flush')
        ;

        $client->getContainer()->set('fos_http_cache.proxy_client.varnish', $mock);

        $client->getCookieJar()->set(new Cookie($session->getName(), 'test'));
        $client->request('GET', '/secured_area/logout');

        $this->assertEquals(302, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }
}
