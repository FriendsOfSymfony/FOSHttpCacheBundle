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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function contentAction($id = null)
    {
        return new Response('content '.$id);
    }

    public function sessionAction()
    {
        $this->container->get('session')->start();

        $response = new Response('session');
        $response->setCache(['max_age' => 60, 'public' => true]);

        return $response;
    }
}
