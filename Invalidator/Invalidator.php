<?php

namespace FOS\HttpCacheBundle\Invalidator;

/**
 * {@inheritdoc}
 */
class Invalidator implements InvalidatorInterface
{
    /**
     * Array of invalidator routes
     *
     * @var array
     */
    protected $invalidatorRoutes = array();

    /**
     * Array of invalidated routes
     *
     * @var array
     */
    protected $invalidatedRoutes = array();

    /**
     * {@inheritdoc}
     */
    public function addInvalidatorRoute($route)
    {
        $this->invalidatorRoutes[] = $route;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvalidatorRoutes()
    {
        return $this->invalidatorRoutes;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvalidatedRoutes()
    {
        return $this->invalidatedRoutes;
    }

    /**
     * {@inheritdoc}
     */
    public function addInvalidatedRoute($route, array $config = array())
    {
        $defaultConfig = array(
            'ignore_extra_params' => true
        );
        $config = \array_merge($defaultConfig, $config);
        $this->invalidatedRoutes[$route] = $config;

        return $this;
    }
}