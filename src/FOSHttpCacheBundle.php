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
use FOS\HttpCacheBundle\DependencyInjection\Compiler\SessionListenerRemovePass;
use FOS\HttpCacheBundle\DependencyInjection\Compiler\TagListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class FOSHttpCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LoggerPass());
        $container->addCompilerPass(new TagListenerPass());
        $container->addCompilerPass(new HashGeneratorPass());
        if (version_compare(Kernel::VERSION, '3.4', '>=')
            && version_compare(Kernel::VERSION, '4.1', '<')
        ) {
            $container->addCompilerPass(new SessionListenerRemovePass());
        }

        // Symfony 3.3 and higher
        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container
                ->registerForAutoconfiguration(ContextProvider::class)
                ->addTag('fos_http_cache.user_context_provider');
        }
    }
}
