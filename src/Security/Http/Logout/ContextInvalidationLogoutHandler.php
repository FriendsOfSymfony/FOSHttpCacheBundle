<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Security\Http\Logout;

use FOS\HttpCacheBundle\UserContextInvalidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

/**
 * @deprecated use ContextInvalidationSessionLogoutHandler in this same namespace as a replacement
 *
 * This handler is deprecated because it never did what it was supposed to do. The session is already invalidated by the SessionLogoutHandler
 * which is always the first logout handler executed
 */
final class ContextInvalidationLogoutHandler implements LogoutHandlerInterface
{
    private $invalidator;

    public function __construct(UserContextInvalidator $invalidator)
    {
        $this->invalidator = $invalidator;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        @trigger_error('Using the ContextInvalidationLogoutHandler is deprecated', E_USER_DEPRECATED);

        if (class_exists(LogoutEvent::class)) {
            // This class no longer works at all with Symfony 5.1, force usage of ContextInvalidationSessionLogoutHandler instead
            // See also: https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/pull/545#discussion_r465089219
            throw new \LogicException(__CLASS__.'::'.__METHOD__.' no longer works with Symfony 5.1. Remove fos_http_cache.user_context.logout_handler from your firewall configuration. See the changelog for version 2.2. for more information.');
        }

        $this->invalidator->invalidateContext($request->getSession()->getId());
    }
}
