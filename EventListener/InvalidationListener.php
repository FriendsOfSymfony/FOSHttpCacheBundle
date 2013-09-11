<?php

namespace Driebit\HttpCacheBundle\EventListener;

use Driebit\HttpCacheBundle\CacheManager;
use Driebit\HttpCacheBundle\Invalidator\InvalidatorCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * On kernel.terminate event, this listener invalidates routes for the current request and flushes the cache manager
 *
 * @author David de Boer <david@driebit.nl>
 */
class InvalidationListener implements EventSubscriberInterface
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
     */
    public function __construct(
        CacheManager $cacheManager,
        InvalidatorCollection $invalidators,
        RouterInterface $router
    ) {
        $this->cacheManager = $cacheManager;
        $this->invalidators = $invalidators;
        $this->router = $router;
    }

    /**
     * Apply invalidators and flush cache manager
     *
     * On kernel.terminate:
     * - see if any invalidators apply to the current request and, if so, add their routes to the cache manager
     * - flush the cache manager in order to send invalidation requests to the HTTP cache.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        // Are there any invalidators configured for the current request route?
        $request = $event->getRequest();
        $requestRoute = $request->attributes->get('_route');
        if (!$this->invalidators->hasInvalidatorRoute($requestRoute)) {
            return $this->cacheManager->flush();
        }

        // Don't invalidate any caches if the request was unsuccessful
        $response = $event->getResponse();
        if (!$response->isSuccessful()) {
            return $this->cacheManager->flush();
        }

        $invalidateRoutes = $this->invalidators->getInvalidatedRoutes($requestRoute);
        foreach ($invalidateRoutes as $route => $config) {
            $params = $this->getInvalidateRouteParameters($route, $request->attributes->get('_route_params'));
            $this->cacheManager->invalidateRoute($route, $params);
        }

        $this->cacheManager->flush();
    }

    /**
     * Get route parameters for the route to invalidated based on current request parameters
     *
     * @param string $route      Route name
     * @param array  $parameters Request parameters
     *
     * @return array Parameters in the invalidate route that match current request parameters
     */
    protected function getInvalidateRouteParameters($route, array $parameters)
    {
        // Which of the request parameters are also supported by the URL to be
        // generated?
        $invalidateRoute = $this->router->getRouteCollection()
            ->get($route)
            ->compile();

        $supportedParams = \array_flip($invalidateRoute->getVariables());

        return \array_intersect_key($parameters, $supportedParams);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(KernelEvents::TERMINATE => 'onKernelTerminate');
    }
}