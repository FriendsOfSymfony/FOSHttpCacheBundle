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

use Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Check for required ControllerListener if TagListener is enabled.
 */
class TagListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (true === $container->getParameter('fos_http_cache.compiler_pass.tag_annotations')
            && !$this->hasControllerListener($container)
        ) {
            throw new \RuntimeException(
                'Tag support requires SensioFrameworkExtraBundle’s ControllerListener for the annotations. '
                .'Please install sensio/framework-extra-bundle and add it to your AppKernel.'
            );
        }
    }

    private function hasControllerListener(ContainerBuilder $container)
    {
        return $container->has('sensio_framework_extra.controller.listener') ||
            $container->has(ControllerListener::class);
    }
}
