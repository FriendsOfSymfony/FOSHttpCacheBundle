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
 * Check for required ControllerListener if TagSubscriber is enabled
 */
class TagSubscriberPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (true === $container->getParameter('fos_http_cache.compiler_pass.tag_annotations')
            && !$container->has('sensio_framework_extra.controller.listener')
        ) {
            throw new \RuntimeException(
                'Tag support requires SensioFrameworkExtraBundle’s ControllerListener for the annotations. '
                .'Please install sensio/framework-extra-bundle and add it to your AppKernel.'
            );
        }
    }
}
