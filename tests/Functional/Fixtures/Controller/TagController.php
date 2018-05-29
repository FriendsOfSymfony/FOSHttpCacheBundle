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
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

class TagController extends Controller
{
    private $responseTagger;

    public function __construct(SymfonyResponseTagger $responseTagger)
    {
        $this->responseTagger = $responseTagger;
    }

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
        $this->responseTagger->addTags(['manual-tag']);

        return $this->render('container.html.twig', [
            'action' => (Kernel::MAJOR_VERSION >= 4 && Kernel::MINOR_VERSION >= 1) ? 'tag_controller::subrequestAction' : 'tag_controller:subrequestAction',
        ]);
    }

    /**
     * @Tag("sub-items")
     */
    public function subrequestAction()
    {
        $this->responseTagger->addTags(['sub-tag']);

        return new Response('subrequest');
    }

    public function twigAction()
    {
        return $this->render('tag.html.twig');
    }
}
