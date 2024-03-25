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
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The RoleProvider adds roles to the UserContext for the hash generation.
 */
class RoleProvider implements ContextProvider
{
    /**
     * Create the role provider with a security context.
     *
     * The token storage is optional to not fail on routes that have no
     * firewall. It is however not valid to call updateUserContext when not in
     * a firewall context.
     */
    public function __construct(
        private ?TokenStorageInterface $tokenStorage = null
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigurationException when called without a security context being set
     */
    public function updateUserContext(UserContext $context): void
    {
        if (null === $this->tokenStorage) {
            throw new InvalidConfigurationException('The context hash URL must be under a firewall.');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            $token = new NullToken();
        }

        $roles = $token->getRoleNames();

        // Order is not important for roles and should not change hash.
        sort($roles);

        $context->addParameter('roles', $roles);
    }
}
