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
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

if (Kernel::MAJOR_VERSION >= 5) {
    class_alias(ResponseEvent::class, 'FOS\HttpCacheBundle\EventListener\TagResponseEvent');
} else {
    class_alias(FilterResponseEvent::class, 'FOS\HttpCacheBundle\EventListener\TagResponseEvent');
}

/**
 * Event handler for the cache tagging tags.
 *
 * @author David de Boer <david@driebit.nl>
 */
class TagListener extends AbstractRuleListener implements EventSubscriberInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var SymfonyResponseTagger
     */
    private $symfonyResponseTagger;

    /**
     * @var ExpressionLanguage|null
     */
    private $expressionLanguage;

    /**
     * @var RuleMatcherInterface
     */
    private $mustInvalidateRule;

    /**
     * @var RuleMatcherInterface
     */
    private $cacheableRule;

    /**
     * Constructor.
     */
    public function __construct(
        CacheManager $cacheManager,
        SymfonyResponseTagger $tagHandler,
        RuleMatcherInterface $cacheableRule,
        RuleMatcherInterface $mustInvalidateRule,
        ExpressionLanguage $expressionLanguage = null
    ) {
        $this->cacheManager = $cacheManager;
        $this->symfonyResponseTagger = $tagHandler;
        $this->cacheableRule = $cacheableRule;
        $this->mustInvalidateRule = $mustInvalidateRule;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * Process the _tags request attribute, which is set when using the Tag
     * annotation.
     *
     * - For a safe (GET or HEAD) request, the tags are set on the response.
     * - For a non-safe request, the tags will be invalidated.
     */
    public function onKernelResponse(TagResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->cacheableRule->matches($request, $response)
            && !$this->mustInvalidateRule->matches($request, $response)
        ) {
            return;
        }

        $tags = $this->getAnnotationTags($request);

        $configuredTags = $this->matchRule($request);
        if ($configuredTags) {
            $tags = array_merge($tags, $configuredTags['tags']);
            foreach ($configuredTags['expressions'] as $expression) {
                $tags[] = $this->evaluateTag($expression, $request);
            }
        }

        if ($this->cacheableRule->matches($request, $response)) {
            // For safe requests (GET and HEAD), set cache tags on response
            $this->symfonyResponseTagger->addTags($tags);
            // BC for symfony < 5.3
            if (method_exists($event, 'isMainRequest') ? $event->isMainRequest() : $event->isMasterRequest()) {
                $this->symfonyResponseTagger->tagSymfonyResponse($response);
            }
        } elseif (count($tags)
            && $this->mustInvalidateRule->matches($request, $response)
        ) {
            $this->cacheManager->invalidateTags($tags);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Get the tags from the annotations on the controller that was used in the
     * request.
     *
     * @return array List of tags affected by the request
     */
    private function getAnnotationTags(Request $request)
    {
        // Check for _tag request attribute that is set when using @Tag
        // annotation
        /** @var $tagConfigurations Tag[] */
        if (!$tagConfigurations = $request->attributes->get('_tag')) {
            return [];
        }

        $tags = [];
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
     * Evaluate a tag that contains expressions.
     *
     * @param string $expression
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

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!$this->expressionLanguage) {
            // the expression comes from controller annotations, we can't detect whether they use expressions while building the configuration
            if (!class_exists(ExpressionLanguage::class)) {
                throw new \RuntimeException('Using the tag annotation requires the '.ExpressionLanguage::class.' to be available.');
            }
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }
}
