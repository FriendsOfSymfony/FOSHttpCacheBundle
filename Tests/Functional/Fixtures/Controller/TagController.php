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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCacheBundle\Configuration\Tag;

class TagController extends Controller
{
    /**
     * @Tag("all-items")
     * @Tag("item-123")
     */
    public function listAction()
    {
        return new Response('All items including 123');
    }

    /**
     * @Tag(expression="'item-'~id")
     */
    public function itemAction(Request $request, $id)
    {
        if (!$request->isMethodSafe()) {
            $this->container->get('fos_http_cache.cache_manager')->invalidateTags(array('all-items'));
        }

        return new Response('Item ' . $id . ' invalidated');
    }

    /**
     * @Tag("items")
     */
    public function errorAction()
    {
        return new Response('Forbidden', 403);
    }
}
