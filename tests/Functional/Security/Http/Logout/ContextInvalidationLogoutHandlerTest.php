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
        $this->markTestSkipped(<<<'EOF'
Session is invalidated in LogoutListener before Proxy Client can invalidate cache.
@see https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/pull/390#issuecomment-333545374
EOF
        );

        $client = static::createClient();
        $session = $client->getContainer()->get('session');

        $token = new UsernamePasswordToken('user', null, 'secured_area', ['ROLE_USER']);
        $session->setId('test');
        $session->set('_security_secured_area', serialize($token));
        $session->save();
        $client->getContainer()->mock(
            'fos_http_cache.proxy_client.varnish',
            Varnish::class
        )
            ->shouldReceive('ban')->once()->with([
                'accept' => 'application/vnd.fos.user-context-hash',
                'Cookie' => '.*test.*',
            ])
            ->shouldReceive('ban')->once()->with([
                'accept' => 'application/vnd.fos.user-context-hash',
                'Authorization' => '.*test.*',
            ])
            ->shouldReceive('flush')->once()
        ;

        $client->getCookieJar()->set(new Cookie($session->getName(), 'test'));
        $client->request('GET', '/secured_area/logout');

        $this->assertEquals(302, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }
}
