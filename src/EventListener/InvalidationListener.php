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

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

if (Kernel::MAJOR_VERSION >= 5) {
    class_alias(TerminateEvent::class, 'FOS\HttpCacheBundle\EventListener\InvalidationTerminateEvent');
} else {
    class_alias(PostResponseEvent::class, 'FOS\HttpCacheBundle\EventListener\InvalidationTerminateEvent');
}

/**
 * On kernel.terminate event, this event handler invalidates routes for the
 * current request and flushes the CacheManager.
 *
 * @author David de Boer <david@driebit.nl>
 */
class InvalidationListener extends AbstractRuleListener implements EventSubscriberInterface
{
    /**
     * Cache manager.
     *
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Router.
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Router.
     *
     * @var ExpressionLanguage|null
     */
    private $expressionLanguage;

    /**
     * @var RuleMatcherInterface
     */
    private $mustInvalidateRule;

    /**
     * Constructor.
     */
    public function __construct(
        CacheManager $cacheManager,
        UrlGeneratorInterface $urlGenerator,
        RuleMatcherInterface $mustInvalidateRule,
        ExpressionLanguage $expressionLanguage = null
    ) {
        $this->cacheManager = $cacheManager;
        $this->urlGenerator = $urlGenerator;
        $this->expressionLanguage = $expressionLanguage;
        $this->mustInvalidateRule = $mustInvalidateRule;
    }

    /**
     * Apply invalidators and flush cache manager.
     *
     * On kernel.terminate:
     * - see if any invalidators apply to the current request and, if so, add
     *   their routes to the cache manager;
     * - flush the cache manager in order to send invalidation requests to the
     *   HTTP cache.
     */
    public function onKernelTerminate(InvalidationTerminateEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Don't invalidate any caches if the request was unsuccessful
        if ($this->mustInvalidateRule->matches($request, $response)) {
            $this->handleInvalidation($request, $response);
        }

        try {
            $this->cacheManager->flush();
        } catch (ExceptionCollection $e) {
            // swallow exception
            // there is the fos_http_cache.event_listener.log to log them
        }
    }

    /**
     * Flush cache manager when kernel exception occurs.
     */
    public function onKernelException()
    {
        try {
            $this->cacheManager->flush();
        } catch (ExceptionCollection $e) {
            // swallow exception
            // there is the fos_http_cache.event_listener.log to log them
        }
    }

    /**
     * Flush cache manager when console terminates or errors.
     *
     * @throws ExceptionCollection If an exception occurs during flush
     */
    public function onConsoleTerminate(ConsoleEvent $event)
    {
        $num = $this->cacheManager->flush();

        if ($num > 0 && OutputInterface::VERBOSITY_VERBOSE <= $event->getOutput()->getVerbosity()) {
            $event->getOutput()->writeln(sprintf('Sent %d invalidation request(s)', $num));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::EXCEPTION => 'onKernelException',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    /**
     * Handle the invalidation annotations and configured invalidators.
     */
    private function handleInvalidation(Request $request, Response $response)
    {
        // Check controller annotations
        if ($paths = $request->attributes->get('_invalidate_path')) {
            $this->invalidatePaths($paths);
        }

        if ($routes = $request->attributes->get('_invalidate_route')) {
            $this->invalidateRoutes($routes, $request);
        }

        // Check configured invalidators
        if (!$invalidatorConfigs = $this->matchRule($request, $response)) {
            return;
        }

        $requestParams = $request->attributes->get('_route_params');
        foreach ($invalidatorConfigs as $route => $config) {
            $path = $this->urlGenerator->generate($route, $requestParams);
            // If extra route parameters should be ignored, strip the query
            // string generated by the Symfony router from the path
            if (isset($config['ignore_extra_params'])
                && $config['ignore_extra_params']
                && $pos = strpos($path, '?')
            ) {
                $path = substr($path, 0, $pos);
            }

            $this->cacheManager->invalidatePath($path);
        }
    }

    /**
     * Invalidate paths from annotations.
     *
     * @param array|InvalidatePath[] $pathConfigurations
     */
    private function invalidatePaths(array $pathConfigurations)
    {
        foreach ($pathConfigurations as $pathConfiguration) {
            foreach ($pathConfiguration->getPaths() as $path) {
                $this->cacheManager->invalidatePath($path);
            }
        }
    }

    /**
     * Invalidate routes from annotations.
     *
     * @param array|InvalidateRoute[] $routes
     */
    private function invalidateRoutes(array $routes, Request $request)
    {
        $values = $request->attributes->all();
        // if there is an attribute called "request", it needs to be accessed through the request.
        $values['request'] = $request;

        foreach ($routes as $route) {
            $params = [];

            if (null !== $route->getParams()) {
                // Iterate over route params and try to evaluate their values
                foreach ($route->getParams() as $key => $value) {
                    if (is_array($value)) {
                        $value = $this->getExpressionLanguage()->evaluate($value['expression'], $values);
                    }

                    $params[$key] = $value;
                }
            }

            $this->cacheManager->invalidateRoute($route->getName(), $params);
        }
    }

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!$this->expressionLanguage) {
            // the expression comes from controller annotations, we can't detect whether they use expressions while building the configuration
            if (!class_exists(ExpressionLanguage::class)) {
                throw new \RuntimeException('Invalidation rules with expressions require '.ExpressionLanguage::class.' to be available.');
            }
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }
}
