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

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

if (!\class_exists(AbstractController::class)) {
    \class_alias(Controller::class, AbstractController::class);
}

class TagController extends AbstractController
{
    private $responseTagger;
    /** @var CacheManager */
    private $cacheManager;

    public function __construct(SymfonyResponseTagger $responseTagger, CacheManager $cacheManager)
    {
        $this->responseTagger = $responseTagger;
        $this->cacheManager = $cacheManager;
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
            $this->cacheManager->invalidateTags(['all-items']);
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
            'action' => version_compare(Kernel::VERSION, '4.1.0', '>=') ? 'tag_controller::subrequestAction' : 'tag_controller:subrequestAction',
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
