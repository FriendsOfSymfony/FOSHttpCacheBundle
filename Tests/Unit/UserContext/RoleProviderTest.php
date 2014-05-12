<?php

namespace FOS\HttpCacheBundle\Tests\Unit\UserContext;

use FOS\HttpCache\UserContext\UserContext;
use FOS\HttpCacheBundle\UserContext\RoleProvider;
use Symfony\Component\Security\Core\Role\Role;

class RoleProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testProvider()
    {
        $roles = array(new Role('ROLE_USER'));

        $token           = \Mockery::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $securityContext = \Mockery::mock('\Symfony\Component\Security\Core\SecurityContext');

        $securityContext->shouldReceive('getToken')->andReturn($token);
        $token->shouldReceive('getRoles')->andReturn($roles);

        $userContext = new UserContext();
        $provider    = new RoleProvider($securityContext);

        $provider->updateUserContext($userContext);

        $this->assertEquals(array(
            'roles' => array('ROLE_USER')
        ), $userContext->getParameters());
    }

    public function testProviderWithoutToken()
    {
        $securityContext = \Mockery::mock('\Symfony\Component\Security\Core\SecurityContext');

        $securityContext->shouldReceive('getToken')->andReturn(null);

        $userContext = new UserContext();
        $provider    = new RoleProvider($securityContext);

        $provider->updateUserContext($userContext);

        $this->assertEmpty($userContext->getParameters());
    }
} 