<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\UserContext;

use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\UserContext;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\Role;

/**
 * The RoleProvider adds roles to the UserContext for the hash generation.
 */
class RoleProvider implements ContextProvider
{
    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    /**
     * Create the role provider with a security context.
     *
     * The token storage is optional to not fail on routes that have no
     * firewall. It is however not valid to call updateUserContext when not in
     * a firewall context.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage = null)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigurationException when called without a security context being set
     */
    public function updateUserContext(UserContext $context)
    {
        if (null === $this->tokenStorage) {
            throw new InvalidConfigurationException('The context hash URL must be under a firewall.');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            if (Kernel::MAJOR_VERSION < 6) {
                return;
            }

            // Symfony 6 no longer provides the AnonymousToken, use the NullToken to generate the same hash for non-logged in users as before
            $token = new NullToken();
        }

        if (method_exists($token, 'getRoleNames')) {
            $roles = $token->getRoleNames();
        } else {
            $roles = array_map(function (Role $role) {
                return $role->getRole();
            }, $token->getRoles());
        }

        // Order is not important for roles and should not change hash.
        sort($roles);

        $context->addParameter('roles', $roles);
    }
}
