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

use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

if (!\class_exists(AbstractController::class)) {
    \class_alias(Controller::class, AbstractController::class);
}

class InvalidationAttributeController extends AbstractController
{

    #[InvalidateRoute('test_noncached')]
    #[InvalidateRoute('test_cached', params: ['id' => 'myhardcodedid'])]
    #[InvalidateRoute('tag_one', params: ['id' => ['expression' => 'id']])]
    public function itemAction($id)
    {
        return new Response("Done $id");
    }

    #[InvalidatePath('/php8/cached')]
    public function otherAction($statusCode)
    {
        return new Response('Done.', $statusCode);
    }

    #[InvalidatePath('/php8/somepath')]
    public function errorAction()
    {
        return new Response('Forbidden', 403);
    }
}
