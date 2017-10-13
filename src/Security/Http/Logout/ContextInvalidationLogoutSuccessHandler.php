<?php

namespace FOS\HttpCacheBundle\Security\Http\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;

class ContextInvalidationSessionLogoutHandler implements LogoutHandlerInterface
{
    private $invalidator;
    private $delegate;

    public function __construct(ContextInvalidationLogoutHandler $invalidator, SessionLogoutHandler $delegate)
    {
        $this->invalidator = $invalidator;
        $this->delegate = $delegate;
    }

    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->invalidator->invalidateContext($request->getSession()->getId());
        $this->delegate->logout($request, $response, $token);
    }
}
