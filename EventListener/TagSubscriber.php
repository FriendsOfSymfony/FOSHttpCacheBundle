<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\Tag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Event handler for the cache tagging tags.
 *
 * @author David de Boer <david@driebit.nl>
 */
class TagSubscriber extends AbstractRuleSubscriber implements EventSubscriberInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * Constructor
     *
     * @param CacheManager       $cacheManager
     * @param ExpressionLanguage $expressionLanguage
     */
    public function __construct(
        CacheManager $cacheManager,
        ExpressionLanguage $expressionLanguage = null
    ) {
        $this->cacheManager = $cacheManager;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * Process the _tags request attribute, which is set when using the Tag
     * annotation
     *
     * - For a safe (GET or HEAD) request, the tags are set on the response.
     * - For a non-safe request, the tags will be invalidated.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $tags = array();

        // Only set cache tags or invalidate them if response is successful
        if ($response->isSuccessful()) {
            $tags = $this->getAnnotationTags($request);
        }

        $configuredTags = $this->matchRule($request, $response);
        if ($configuredTags) {
            foreach ($configuredTags['tags'] as $tag) {
                $tags[] = $tag;
            }
            foreach ($configuredTags['expressions'] as $expression) {
                $tags[] = $this->evaluateTag($expression, $request);
            }
        }

        if (!count($tags)) {
            return;
        }

        if ($request->isMethodSafe()) {
            // For safe requests (GET and HEAD), set cache tags on response
            $this->cacheManager->tagResponse($response, $tags);
        } else {
            // For non-safe methods, invalidate the tags
            $this->cacheManager->invalidateTags($tags);
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

    /**
     * Get the tags from the annotations on the controller that was used in the
     * request.
     *
     * @param Request $request
     *
     * @return array List of tags affected by the request.
     */
    private function getAnnotationTags(Request $request)
    {
        // Check for _tag request attribute that is set when using @Tag
        // annotation
        /** @var $tagConfigurations Tag[] */
        if (!$tagConfigurations = $request->attributes->get('_tag')) {
            return array();
        }

        $tags = array();
        foreach ($tagConfigurations as $tagConfiguration) {
            if (null !== $tagConfiguration->getExpression()) {
                $tags[] = $this->evaluateTag(
                    $tagConfiguration->getExpression(),
                    $request
                );
            } else {
                $tags = array_merge($tags, $tagConfiguration->getTags());
            }
        }

        return $tags;
    }

    /**
     * Evaluate a tag that contains expressions
     *
     * @param string  $expression
     * @param Request $request
     *
     * @return string Evaluated tag
     */
    private function evaluateTag($expression, Request $request)
    {
        $values = $request->attributes->all();
        // if there is an attribute called "request", it needs to be accessed through the request.
        $values['request'] = $request;

        return $this->getExpressionLanguage()->evaluate($expression, $values);
    }

    /**
     * Delay instantiating the expression language instance until we need it,
     * to support a setup with only symfony 2.3.
     *
     * @return ExpressionLanguage
     */
    private function getExpressionLanguage()
    {
        if (!$this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }
}
