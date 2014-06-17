<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    private $invalidatorRoutes = array();

    /**
     * Array of invalidated routes
     *
     * @var array
     */
    private $invalidatedRoutes = array();

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
