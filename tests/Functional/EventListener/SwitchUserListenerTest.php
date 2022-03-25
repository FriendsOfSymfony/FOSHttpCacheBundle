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
use FOS\HttpCacheBundle\Tests\Functional\SessionHelperTrait;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;

if (!\class_exists(KernelBrowser::class)) {
    \class_alias(Client::class, KernelBrowser::class);
}

class SwitchUserListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;
    use SessionHelperTrait;

    private const FIREWALL_NAME = 'secured_area';
    private const SESSION_ID = 'test';
    private $sessionName;
    private static $overrideService = false;

    public function testSwitchUserCompatibility()
    {
        $client = static::createClient();
        $this->callInRequestContext($client, [$this, 'loginAsAdmin']);
        $client->getCookieJar()->set(new Cookie($this->sessionName, self::SESSION_ID));

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
            ->with(['fos_http_cache_hashlookup-test']);

        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(1);

        $kernel = static::createKernel();
        $kernel->boot();
        $kernel->getContainer()->set('fos_http_cache.proxy_client.varnish', $mock);

        $client = static::createClient();

        $this->callInRequestContext($client, [$this, 'loginAsAdmin']);

        $client->request('GET', '/secured_area/switch_user?_switch_user=user');
    }

    public function loginAsAdmin(RequestEvent $requestEvent)
    {
        $session = $requestEvent->getRequest()->getSession();
        if (Kernel::MAJOR_VERSION >= 6) {
            $token = new UsernamePasswordToken(new InMemoryUser('admin', 'admin', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']), self::FIREWALL_NAME, ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']);
        } else {
            $token = new UsernamePasswordToken(new User('admin', 'admin', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']), null, self::FIREWALL_NAME, ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']);
        }

        $session->setId(self::SESSION_ID);
        $session->set(sprintf('_security_%s', self::FIREWALL_NAME), serialize($token));
        $session->save();

        $this->sessionName = $session->getName();
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
}
