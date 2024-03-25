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
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\EventListener\SessionLogoutListener;

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
