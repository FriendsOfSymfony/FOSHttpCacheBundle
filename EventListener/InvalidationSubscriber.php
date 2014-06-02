<?php

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\Invalidator\InvalidatorCollection;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * On kernel.terminate event, this event handler invalidates routes for the
 * current request and flushes the CacheManager.
 *
 * @author David de Boer <david@driebit.nl>
 */
class InvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * Cache manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Invalidator collection
     *
     * @var InvalidatorCollection
     */
    protected $invalidators;

    /**
     * Router
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * Constructor
     *
     * @param CacheManager          $cacheManager
     * @param InvalidatorCollection $invalidators
     * @param RouterInterface       $router
     * @param ExpressionLanguage    $expressionLanguage
     */
    public function __construct(
        CacheManager $cacheManager,
        InvalidatorCollection $invalidators,
        RouterInterface $router,
        ExpressionLanguage $expressionLanguage = null
    ) {
        $this->cacheManager = $cacheManager;
        $this->invalidators = $invalidators;
        $this->router = $router;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * Apply invalidators and flush cache manager
     *
     * On kernel.terminate:
     * - see if any invalidators apply to the current request and, if so, add
     *   their routes to the cache manager;
     * - flush the cache manager in order to send invalidation requests to the
     *   HTTP cache.
     *
     * @param PostResponseEvent $event
     *
     * @return array Paths that were flushed from the invalidation queue
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Don't invalidate any caches if the request was unsuccessful
        if ($response->isSuccessful()) {
            $this->handleInvalidation($request);
        }

        try {
            $this->cacheManager->flush();
        } catch (ExceptionCollection $e) {
            // swallow exception
            // there is the fos_http_cache.event_listener.log to log them
        }
    }

    /**
     * Flush cache manager when kernel exception occurs
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
     * Flush cache manager when console terminates or errors
     *
     * @throws ExceptionCollection If an exception occurs during flush.
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
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::TERMINATE  => 'onKernelTerminate',
            KernelEvents::EXCEPTION  => 'onKernelException',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
            ConsoleEvents::EXCEPTION => 'onConsoleTerminate'
        );
    }

    /**
     * Handle the invalidation annotations and configured invalidators.
     *
     * @param Request $request
     */
    protected function handleInvalidation(Request $request)
    {
        // Check controller annotations
        if ($paths = $request->attributes->get('_invalidate_path')) {
            $this->invalidatePaths($paths);
        }

        if ($routes = $request->attributes->get('_invalidate_route')) {
            $this->invalidateRoutes($routes, $request);
        }

        // Check configured invalidators
        $requestRoute = $request->attributes->get('_route');
        if (!$this->invalidators->hasInvalidatorRoute($requestRoute)) {
            return;
        }

        $requestParams = $request->attributes->get('_route_params');
        $invalidators = $this->invalidators->getInvalidators($requestRoute);
        foreach ($invalidators as $invalidator) {
            foreach ($invalidator->getInvalidatedRoutes() as $route => $config) {
                $path = $this->router->generate($route, $requestParams);

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
    }

    /**
     * Invalidate paths from annotations
     *
     * @param array|InvalidatePath[] $pathConfigurations
     */
    protected function invalidatePaths(array $pathConfigurations)
    {
        foreach ($pathConfigurations as $pathConfiguration) {
            foreach ($pathConfiguration->getPaths() as $path) {
                $this->cacheManager->invalidatePath($path);
            }
        }
    }

    /**
     * Invalidate routes from annotations
     *
     * @param array|InvalidateRoute[] $routes
     * @param Request                 $request
     */
    protected function invalidateRoutes(array $routes, Request $request)
    {
        foreach ($routes as $route) {
            $params = array();

            if (null !== $route->getParams()) {
                // Iterate over route params and try to evaluate their values
                foreach ($route->getParams() as $key => $value) {
                    try {
                        $value = $this->getExpressionLanguage()->evaluate($value, $request->attributes->all());
                    } catch (SyntaxError $e) {
                        // If a syntax error occurred, we assume the param was
                        // no expression
                    }

                    $params[$key] = $value;
                }
            }

            $this->cacheManager->invalidateRoute($route->getName(), $params);
        }
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
