<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\UserContext;

use FOS\HttpCache\UserContext\UserContext;
use FOS\HttpCacheBundle\UserContext\RoleProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;

class RoleProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testProvider()
    {
        $roles = [new Role('ROLE_USER')];

        $token = \Mockery::mock(TokenInterface::class);

        $securityContext = $this->getTokenStorageMock();
        $securityContext->shouldReceive('getToken')->andReturn($token);
        $token->shouldReceive('getRoles')->andReturn($roles);

        $userContext = new UserContext();
        $provider = new RoleProvider($securityContext);

        $provider->updateUserContext($userContext);

        $this->assertEquals([
            'roles' => ['ROLE_USER'],
        ], $userContext->getParameters());
    }

    public function testProviderWithoutToken()
    {
        $securityContext = $this->getTokenStorageMock();
        $securityContext->shouldReceive('getToken')->andReturn(null);

        $userContext = new UserContext();
        $provider = new RoleProvider($securityContext);

        $provider->updateUserContext($userContext);

        $this->assertEmpty($userContext->getParameters());
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testNotUnderFirewall()
    {
        $roleProvider = new RoleProvider();
        $roleProvider->updateUserContext(new UserContext());
    }

    private function getTokenStorageMock()
    {
        return \Mockery::mock(TokenStorageInterface::class);
    }
}
