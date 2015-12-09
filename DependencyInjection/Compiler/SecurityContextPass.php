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
use Symfony\Component\DependencyInjection\Reference;

/**
 * In Symfony < 2.6, replace the new security.token_storage service with the  
 * deprecated security.context service. 
 */
class SecurityContextPass implements CompilerPassInterface
{
    const ROLE_PROVIDER_SERVICE = 'fos_http_cache.user_context.role_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::ROLE_PROVIDER_SERVICE)) {
            return;
        }

        if (!$container->has('security.token_storage') && $container->has('security.context')) {
            $definition = $container->getDefinition(self::ROLE_PROVIDER_SERVICE);
            $definition->replaceArgument(0, new Reference('security.context'));
        }
    }
}
