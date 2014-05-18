<?php

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    ) {
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
        $configuredTags = $this->matchConfiguration($request, $response) ?: array();
        /*
        foreach ($configuredTags as $id => $tag) {
            $configuredTags[$id] = $this->expressionLanguage->evaluate($tag, array(
                'request' => $request,
                'response' => $response,
            ));
        }
        */

        $uniqueTags = array_values(array_unique(array_merge($tags, $configuredTags)));

        if (!count($uniqueTags)) {
            return;
        }

        if ($request->isMethodSafe()) {
            // For safe requests (GET and HEAD), set cache tags on response
            $this->cacheManager->tagResponse($response, $uniqueTags);
        } else {
            // For non-safe methods, invalidate the tags
            $this->cacheManager->invalidateTags($uniqueTags);
        }
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
                $tags[] = $this->expressionLanguage->evaluate(
                    $tagConfiguration->getExpression(),
                    $request->attributes->all()
                );
            } else {
                $tags = array_merge($tags, $tagConfiguration->getTags());
            }
        }

        return $tags;
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
