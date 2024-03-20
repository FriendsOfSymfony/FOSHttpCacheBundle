<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Fixtures\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class TestController extends AbstractController
{
    public function contentAction($id = null): Response
    {
        return new Response('content '.$id);
    }

    public function sessionAction(Request $request): Response
    {
        $request->getSession()->start();

        $response = new Response('session');
        $response->setCache(['max_age' => 60, 'public' => true]);

        return $response;
    }

    public function switchUserAction(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            throw new \LogicException('No user in token');
        }

        return new Response($user->getUserIdentifier());
    }
}
