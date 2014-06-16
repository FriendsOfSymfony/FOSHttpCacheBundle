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
 * Attach Symfony2 logger to cache manager
 */
class LoggerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('logger') || !$container->has('fos_http_cache.event_listener.log')) {
            return;
        }

        $subscriber = $container->getDefinition('fos_http_cache.event_listener.log')
            ->setAbstract(false);

        $container->getDefinition('fos_http_cache.cache_manager')
            ->addMethodCall('addSubscriber', array($subscriber));
    }
}
