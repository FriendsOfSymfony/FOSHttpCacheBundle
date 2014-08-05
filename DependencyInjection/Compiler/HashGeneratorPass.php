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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add tagged provider to the hash generator for user context
 */
class HashGeneratorPass implements CompilerPassInterface
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

        $providers = array();
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $parameters) {
            $providers[] = new Reference($id);
        }

        if (!count($providers)) {
            throw new InvalidConfigurationException('No user context providers found. Either tag providers or disable fos_http_cache.user_context');
        }

        $definition->addArgument($providers);
    }
}
