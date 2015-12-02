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

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\UserContext;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * The RoleProvider adds roles to the UserContext for the hash generation.
 */
class RoleProvider implements ContextProviderInterface
{
    /**
     * @var SecurityContextInterface|null
     */
    private $context;

    /**
     * Create the role provider with a security context.
     *
     * The security context is optional to not fail on routes that have no
     * firewall. It is however not valid to call updateUserContext when not in
     * a firewall context.
     *
     * @param SecurityContextInterface|TokenStorageInterface $context
     */
    public function __construct($context = null)
    {
        if ($context
            && !$context instanceof SecurityContextInterface
            && !$context instanceof TokenStorageInterface
        ) {
            throw new \InvalidArgumentException(
                'Context must implement either TokenStorageInterface or SecurityContextInterface'
            );
        }

        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidConfigurationException when called without a security context being set.
     */
    public function updateUserContext(UserContext $context)
    {
        if (null === $this->context) {
            throw new InvalidConfigurationException('The context hash URL must be under a firewall.');
        }

        if (null === $token = $this->context->getToken()) {
            return;
        }

        $roles = array_map(function (RoleInterface $role) {
            return $role->getRole();
        }, $token->getRoles());

        // Order is not important for roles and should not change hash.
        sort($roles);

        $context->addParameter('roles', $roles);
    }
}
