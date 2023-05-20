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
    public function process(ContainerBuilder $container): void
    {
        if (true === $container->getParameter('fos_http_cache.compiler_pass.tag_annotations')
            && !$this->hasControllerListener($container)
        ) {
            throw new \RuntimeException(
                'Tag annotations are enabled by default because otherwise things could silently not work.'
                .' The annotations require the SensioFrameworkExtraBundle ControllerListener. If you do not use'
                .' annotations for tags, set "fos_http_cache.tags.annotations.enabled: false". Otherwise install'
                .' sensio/framework-extra-bundle and enabled it in your kernel.'
            );
        }
    }

    private function hasControllerListener(ContainerBuilder $container): bool
    {
        return $container->has('sensio_framework_extra.controller.listener') ||
            $container->has(ControllerListener::class);
    }
}
