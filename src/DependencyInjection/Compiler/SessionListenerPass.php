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
 * If no session_listener is configured, change the hasSessionListener flag of
 * the UserContextListener to false to avoid the header
 * AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER from being leaked to
 * the client.
 */
class SessionListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('session_listener')) {
            return;
        }

        if ($container->hasDefinition('fos_http_cache.event_listener.user_context')) {
            $contextListener = $container->getDefinition('fos_http_cache.event_listener.user_context');
            $contextListener->setArgument(5, false);
        }
    }
}
