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
 * Undo our workarounds for the Symfony session listener when the session
 * system of Symfony has not been activated.
 *
 * - Set the hasSessionListener option of the UserContextListener to false to
 *   avoid the AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER being
 *   leaked to clients.
 * - Remove the session listener decorator we configured.
 */
class SessionListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('session_listener')) {
            return;
        }

        if ($container->hasDefinition('fos_http_cache.event_listener.user_context')) {
            $contextListener = $container->getDefinition('fos_http_cache.event_listener.user_context');
            $contextListener->setArgument(5, false);
        }
        $container->removeDefinition('fos_http_cache.user_context.session_listener');
    }
}
