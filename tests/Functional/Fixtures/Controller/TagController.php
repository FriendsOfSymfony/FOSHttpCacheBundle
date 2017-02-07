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

use FOS\HttpCacheBundle\Configuration\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if (!$request->isMethodCacheable()) {
            $this->container->get('fos_http_cache.cache_manager')->invalidateTags(['all-items']);
        }

        return new Response('Item '.$id.' invalidated');
    }

    /**
     * @Tag("items")
     */
    public function errorAction()
    {
        return new Response('Forbidden', 403);
    }

    /**
     * @Tag("manual-items")
     */
    public function manualAction()
    {
        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags(['manual-tag']);

        return $this->render('::container.html.twig');
    }

    /**
     * @Tag("sub-items")
     */
    public function subrequestAction()
    {
        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags(['sub-tag']);

        return new Response('subrequest');
    }

    public function twigAction()
    {
        return $this->render('::tag.html.twig');
    }
}
