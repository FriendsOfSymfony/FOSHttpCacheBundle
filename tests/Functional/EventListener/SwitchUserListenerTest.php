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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

if (!\class_exists(KernelBrowser::class)) {
    \class_alias(Client::class, KernelBrowser::class);
}

class SwitchUserListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private const FIREWALL_NAME = 'secured_area';
    private $sessionName;
    private static $overrideService = false;

    public function testSwitchUserCompatibility()
    {
        $client = static::createClient();
        $this->loginTestUser($client);

        $client->request('GET', '/secured_area/switch_user?_switch_user=user');
        $client->request('GET', '/secured_area/switch_user');
        $this->assertSame('user', substr($client->getResponse()->getContent(), 0, 2000));

        $client->request('GET', '/secured_area/switch_user?_switch_user=_exit');
        $client->request('GET', '/secured_area/switch_user');
        $this->assertSame('admin', substr($client->getResponse()->getContent(), 0, 2000));
    }

    public function testInvalidateContext()
    {
        self::$overrideService = true;

        $mock = \Mockery::mock(Varnish::class);
        $mock->shouldReceive('invalidateTags')
            ->once()
            ->with(\Mockery::any())
        ;

        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1);

        $client = static::createClient();

        $container = method_exists($this, 'getContainer') ? self::getContainer() : $client->getContainer();
        $container->set('fos_http_cache.proxy_client.varnish', $mock);

        $this->loginTestUser($client);

        $client->request('GET', '/secured_area/switch_user?_switch_user=user');
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);
        \assert($kernel instanceof \AppKernel);
        if (static::$overrideService) {
            $kernel->addServiceOverride('override_varnish_proxy.yml');
        }

        return $kernel;
    }

    private function getUser(): UserInterface
    {
        if (Kernel::MAJOR_VERSION >= 6) {
            $testUser = new InMemoryUser('admin', 'admin', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']);
        } else {
            $testUser = new User('admin', 'admin', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']);
        }

        return $testUser;
    }

    private function loginTestUser(KernelBrowser $client)
    {
        if (method_exists($client, 'loginUser')) {
            $client->loginUser($this->getUser(), self::FIREWALL_NAME);

            return;
        }

        $container = method_exists($this, 'getContainer') ? self::getContainer() : (property_exists($this, 'container') ? self::$container : $client->getContainer());
        $session = $container->get('session');

        $user = $this->getUser();

        $token = new UsernamePasswordToken($user, null, self::FIREWALL_NAME, $user->getRoles());
        $session->set('_security_'.self::FIREWALL_NAME, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}
