<?php

namespace FOS\HttpCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Check for required ControllerListener if TagListener is enabled
 */
class TagListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('fos_http_cache.tag_listener')
            && !$container->has('sensio_framework_extra.controller.listener')
        ) {
            throw new \RuntimeException(
                'The TagListener requires SensioFrameworkExtraBundle’s ControllerListener. '
                . 'Please install sensio/framework-extra-bundle and add it to your AppKernel.'
            );
        }
    }
}
