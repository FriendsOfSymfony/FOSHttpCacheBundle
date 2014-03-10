<?php

namespace FOS\HttpCacheBundle;

use FOS\HttpCacheBundle\DependencyInjection\Compiler\LoggerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FOSHttpCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LoggerPass());
    }
}
