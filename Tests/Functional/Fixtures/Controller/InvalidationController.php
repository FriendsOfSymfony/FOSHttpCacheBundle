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
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\Configuration\InvalidatePath;

class InvalidationController extends Controller
{
    /**
     * @InvalidateRoute("test_noncached")
     * @InvalidateRoute("test_cached", params={"id" = "myhardcodedid"})
     * @InvalidateRoute("tag_one", params={"id" = {"expression"="id"}})
     */
    public function itemAction($id)
    {
        return new Response("Done $id");
    }

    /**
     * @InvalidatePath("/cached")
     */
    public function otherAction()
    {
        return new Response('Done.');
    }

    /**
     * @InvalidatePath("/somepath")
     */
    public function errorAction()
    {
        return new Response('Forbidden', 403);
    }
}
