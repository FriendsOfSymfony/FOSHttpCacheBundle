<?php

namespace FOS\HttpCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * E
 */
class LoggerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('logger')) {
            return;
        }

        $subscriber = $container->getDefinition('fos_http_cache.proxy.log_subscriber')
            ->setAbstract(false);

        $container->getDefinition('fos_http_cache.cache_manager')
            ->addMethodCall('addSubscriber', array($subscriber));
    }
}
