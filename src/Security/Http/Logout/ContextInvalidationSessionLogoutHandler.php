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
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\EventListener\SessionLogoutListener;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;

if (Kernel::MAJOR_VERSION >= 6) {
    final class ContextInvalidationSessionLogoutHandler extends SessionLogoutListener
    {
        private $invalidator;

        public function __construct(UserContextInvalidator $invalidator)
        {
            $this->invalidator = $invalidator;
        }

        public function onLogout(LogoutEvent $event): void
        {
            if ($event->getRequest()->hasSession()) {
                $this->invalidator->invalidateContext($event->getRequest()->getSession()->getId());
            }

            parent::onLogout($event);
        }
    }
} else {
    final class ContextInvalidationSessionLogoutHandler extends SessionLogoutHandler
    {
        private $invalidator;

        public function __construct(UserContextInvalidator $invalidator)
        {
            $this->invalidator = $invalidator;
        }

        public function logout(Request $request, Response $response, TokenInterface $token)
        {
            $this->invalidator->invalidateContext($request->getSession()->getId());
            parent::logout($request, $response, $token);
        }
    }
}
