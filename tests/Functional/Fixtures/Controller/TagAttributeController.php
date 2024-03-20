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
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TagAttributeController extends AbstractController
{
    private SymfonyResponseTagger $responseTagger;

    public function __construct(SymfonyResponseTagger $responseTagger)
    {
        $this->responseTagger = $responseTagger;
    }

    #[Tag('all-items')]
    #[Tag('item-123')]
    public function listAction(): Response
    {
        return new Response('All items including 123');
    }

    #[Tag(expression: new Expression("'item-'~id"))]
    public function itemAction(Request $request, string $id, CacheManager $cacheManager): Response
    {
        if (!$request->isMethodCacheable()) {
            $cacheManager->invalidateTags(['all-items']);
        }

        return new Response('Item '.$id.' invalidated');
    }

    #[Tag('items')]
    public function errorAction(): Response
    {
        return new Response('Forbidden', 403);
    }

    #[Tag('manual-items')]
    public function manualAction(): Response
    {
        $this->responseTagger->addTags(['manual-tag']);

        return $this->render('container.html.twig', [
            'action' => 'FOS\\HttpCacheBundle\\Tests\\Functional\\Fixtures\\Controller\\TagAttributeController::subrequestAction',
        ]);
    }

    #[Tag('sub-items')]
    public function subrequestAction(): Response
    {
        $this->responseTagger->addTags(['sub-tag']);

        return new Response('subrequest');
    }

    public function emptyAction(): Response
    {
        return new Response('');
    }

    public function twigAction(): Response
    {
        return $this->render('tag.html.twig');
    }
}
