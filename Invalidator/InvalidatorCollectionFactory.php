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
 * Class InvalidatorCollectionFactory
 */
class InvalidatorCollectionFactory
{
    /**
     * Get invalidator collection
     *
     * @param array $configs Config array
     *
     * @return InvalidatorCollection Invalidator collection
     */
    public static function getInvalidatorCollection(array $configs)
    {
        $collection = new InvalidatorCollection();

        foreach ($configs as $name => $config) {
            $invalidator = new Invalidator();

            foreach ($config['origin_routes'] as $route) {
                $invalidator->addInvalidatorRoute($route);
            }

            foreach ($config['invalidate_routes'] as $route => $routeConfig) {
                $invalidator->addInvalidatedRoute($route, $routeConfig);
            }

            $collection->addInvalidator($invalidator);
        }

        return $collection;
    }
}
