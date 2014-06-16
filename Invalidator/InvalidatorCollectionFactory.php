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

class InvalidatorCollectionFactory
{
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
