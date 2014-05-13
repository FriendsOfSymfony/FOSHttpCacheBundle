<?php

namespace FOS\HttpCacheBundle\UserContext;

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\UserContext;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * RoleProvider add roles to the UserContext for the hash generation
 */
class RoleProvider implements ContextProviderInterface
{
    private $context;

    public function __construct(SecurityContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function updateUserContext(UserContext $context)
    {
        if (null === $token = $this->context->getToken()) {
            return;
        }

        $roles = array_map(function ($role) {
            return $role->getRole();
        }, $token->getRoles());

        // Order is not important for roles and should not change hash.
        sort($roles);

        $context->addParameter('roles', $roles);
    }
}
