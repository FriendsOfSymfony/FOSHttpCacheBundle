<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle;

use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCacheBundle\DependencyInjection\Compiler\HashGeneratorPass;
use FOS\HttpCacheBundle\DependencyInjection\Compiler\LoggerPass;
use FOS\HttpCacheBundle\DependencyInjection\Compiler\SessionListenerPass;
use FOS\HttpCacheBundle\DependencyInjection\Compiler\TagListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FOSHttpCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new LoggerPass());
        $container->addCompilerPass(new TagListenerPass());
        $container->addCompilerPass(new HashGeneratorPass());
        $container->addCompilerPass(new SessionListenerPass());

        $container
            ->registerForAutoconfiguration(ContextProvider::class)
            ->addTag('fos_http_cache.user_context_provider');
    }
}
