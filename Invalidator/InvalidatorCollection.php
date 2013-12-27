<?php

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

    public function addInvalidator(InvalidatorInterface $invalidator)
    {
        $this->invalidators[] = $invalidator;

        foreach ($invalidator->getInvalidatorRoutes() as $route) {
            if (!isset($this->invalidatorRoutes[$route])) {
                $this->invalidatorRoutes[$route] = array();
            }
            $this->invalidatorRoutes[$route][] = $invalidator;
        }
    }

    public function hasInvalidatorRoute($route)
    {
        return isset($this->invalidatorRoutes[$route]);
    }

    /**
     * @param string $route
     *
     * @return Invalidator[]
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

    public function getInvalidatedRoutes($route)
    {
        $routes = array();

        foreach ($this->getInvalidators($route) as $invalidator) {
            $routes = \array_merge($routes, $invalidator->getInvalidatedRoutes());
        }

        return $routes;
    }
}