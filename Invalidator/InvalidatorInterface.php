<?php

namespace FOS\HttpCacheBundle\Invalidator;

/**
 * An invalidator
 *
 * An invalidator is a combination of one or more invalidator routes and one or
 * more invalidated routes. When one of the invalidator routes is requested,
 * an invalidation of all invalidated routes is triggered.
 *
 * @author David de Boer <david@driebit.nl>
 */
interface InvalidatorInterface
{
    /**
     * Add a route that triggers invalidation
     *
     * @param string $route Route name
     *
     * @return InvalidatorInterface
     */
    public function addInvalidatorRoute($route);

    /**
     * Get routes that trigger invalidation
     *
     * @return array
     */
    public function getInvalidatorRoutes();

    /**
     * A a route that will be invalidated
     *
     * @param string $route  Route name
     * @param array  $config Additional configuration
     *
     * @return InvalidatorInterface
     */
    public function addInvalidatedRoute($route, array $config);

    /**
     * Get routes that will be invalidated
     *
     * @return array
     */
    public function getInvalidatedRoutes();
}