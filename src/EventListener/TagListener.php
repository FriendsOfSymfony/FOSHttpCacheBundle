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
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;

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
     * @var ExpressionLanguage
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
     * @var ControllerResolver
     */
    private $controllerResolver;

    /**
     * Constructor.
     */
    public function __construct(
        CacheManager $cacheManager,
        SymfonyResponseTagger $tagHandler,
        RuleMatcherInterface $cacheableRule,
        RuleMatcherInterface $mustInvalidateRule,
        ControllerResolver $controllerResolver,
        ExpressionLanguage $expressionLanguage = null
    ) {
        $this->cacheManager = $cacheManager;
        $this->symfonyResponseTagger = $tagHandler;
        $this->cacheableRule = $cacheableRule;
        $this->mustInvalidateRule = $mustInvalidateRule;
        $this->controllerResolver = $controllerResolver;
        $this->expressionLanguage = $expressionLanguage ?: new ExpressionLanguage();
    }

    public function onKernelRequest(RequestEvent $event) {

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

        if (
            method_exists(\ReflectionProperty::class, 'getAttributes') &&
            $controller = $this->controllerResolver->getController($request)
        ) {
            $class = new \ReflectionClass($controller[0]);
            $method = $class->getMethod($controller[1]);
            $tags = [];
            foreach ($class->getAttributes() as $classAttribute) {
                $tags[] = $classAttribute->newInstance();
            }
            foreach ($method->getAttributes() as $methodAttribute) {
                $tags[] = $methodAttribute->newInstance();
            }

            $request->attributes->set(
                '_tag',
                array_merge($tags, $request->attributes->get('_tag', []))
            );
        }

        $tags = $this->getAnnotationTags($request);

        //if (empty($tags) && )

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
            if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
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
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
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

        return $this->expressionLanguage->evaluate($expression, $values);
    }
}
