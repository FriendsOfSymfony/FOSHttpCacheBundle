<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\Tag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Event handler for the cache tagging tags.
 *
 * @author David de Boer <david@driebit.nl>
 */
class TagSubscriber implements EventSubscriberInterface
{
    /**
     * @var CacheManager
     *
     * Cache manager
     */
    protected $cacheManager;

    /**
     * Constructor
     *
     * @param CacheManager       $cacheManager
     * @param ExpressionLanguage $expressionLanguage
     */
    public function __construct(
        CacheManager $cacheManager,
        ExpressionLanguage $expressionLanguage = null
    )
    {
        $this->cacheManager = $cacheManager;
        $this->expressionLanguage = $expressionLanguage ?: new ExpressionLanguage();
    }

    /**
     * Process the _tags request attribute, which is set when using the Tag
     * annotation
     *
     * - For a safe (GET or HEAD) request, the tags are set on the response.
     * - For a non-safe request, the tags will be invalidated.
     *
     * @param FilterResponseEvent $event Event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        // Check for _tag request attribute that is set when using @Tag
        // annotation
        /** @var $tagConfigurations Tag[] */
        if (!$tagConfigurations = $request->attributes->get('_tag')) {
            return;
        }

        $response = $event->getResponse();

        // Only set cache tags or invalidate them if response is successful
        if (!$response->isSuccessful()) {
            return;
        }

        $tags = array();
        foreach ($tagConfigurations as $tagConfiguration) {
            if (null !== $tagConfiguration->getExpression()) {
                $tags[] = $this->expressionLanguage->evaluate(
                    $tagConfiguration->getExpression(),
                    $request->attributes->all()
                );
            } else {
                $tags = array_merge($tags, $tagConfiguration->getTags());
            }
        }

        $uniqueTags = array_unique($tags);

        if ($request->isMethodSafe()) {
            // For safe requests (GET and HEAD), set cache tags on response
            $this->cacheManager->tagResponse($response, $uniqueTags);
        } else {
            // For non-safe methods, invalidate the tags
            $this->cacheManager->invalidateTags($uniqueTags);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse'
        );
    }
}
