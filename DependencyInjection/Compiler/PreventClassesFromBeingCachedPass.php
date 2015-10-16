<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to remove some classes from Symfony's class cache to prevent
 * from redeclaration in early cache lookup phase
 * (see https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/issues/242).
 */
class PreventClassesFromBeingCachedPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // it's enough to load classes via autoloading in the cache build phase
        // to prevent Symfony from adding them (and all their inheriting classes)
        // to the class cache
        // (see Symfony\Component\ClassLoader\ClassCollectionLoader::load)

        if ($container->hasParameter('fos_http_cache.invalidation.enabled') &&
            $container->hasParameter('fos_http_cache.proxy_client.symfony.base_url')
        ) {
            // class Symfony\Component\EventDispatcher\Event should not be cashed
            // if invalidation and SymfonyCache is enabled
            class_exists('FOS\\HttpCache\\SymfonyCache\\CacheEvent');
        }
    }
}