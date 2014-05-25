<?php

namespace FOS\HttpCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add tagged provider to the hash generator for user context
 */
class UserContextListenerPass implements CompilerPassInterface
{
    const TAG_NAME = "fos_http_cache.user_context_provider";

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('fos_http_cache.user_context.hash_generator')) {
            return;
        }

        $definition = $container->getDefinition('fos_http_cache.user_context.hash_generator');

        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $parameters) {
            $definition->addMethodCall('registerProvider', array(new Reference($id)));
        }
    }
}
