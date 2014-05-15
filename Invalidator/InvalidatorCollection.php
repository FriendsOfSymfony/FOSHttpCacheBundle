<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Invalidator;

/**
 * A collection of invalidators
 *
 * @author David de Boer <david@driebit.nl>
 */
class InvalidatorCollection
{
    /**
     * Array of invalidators
     *
     * @var InvalidatorInterface[]
     */
    protected $invalidators = array();

    /**
     * Array of invalidator routes for quick lookup
     *
     * @var array
     */
    protected $invalidatorRoutes = array();

    /**
     * Add invalidator
     *
     * @param InvalidatorInterface $invalidator Invalidator
     *
     * @return InvalidatorCollection self Object
     */
    public function addInvalidator(InvalidatorInterface $invalidator)
    {
        $this->invalidators[] = $invalidator;

        foreach ($invalidator->getInvalidatorRoutes() as $route) {
            if (!isset($this->invalidatorRoutes[$route])) {
                $this->invalidatorRoutes[$route] = array();
            }
            $this->invalidatorRoutes[$route][] = $invalidator;
        }

        return $this;
    }

    /**
     * Return if has invalidator routes
     *
     * @param string $route Router
     *
     * @return bool Has invalidator Routes
     */
    public function hasInvalidatorRoute($route)
    {
        return isset($this->invalidatorRoutes[$route]);
    }

    /**
     * Get all invalidators
     *
     * @param string $route Route
     *
     * @return Invalidator[] Invalidators
     */
    public function getInvalidators($route)
    {
        $invalidators = new \SplObjectStorage;

        if (isset($this->invalidatorRoutes[$route])) {

            foreach ($this->invalidatorRoutes[$route] as $invalidator) {
                $invalidators->attach($invalidator);
            }
        }

        return $invalidators;
    }

    /**
     * Get invalidated routes
     *
     * @param string $route Route
     *
     * @return array Routes array
     */
    public function getInvalidatedRoutes($route)
    {
        $routes = array();

        foreach ($this->getInvalidators($route) as $invalidator) {
            $routes = \array_merge($routes, $invalidator->getInvalidatedRoutes());
        }

        return $routes;
    }
}
