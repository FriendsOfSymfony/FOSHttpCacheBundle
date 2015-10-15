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
 * Compiler pass to remove some classes from Symfony's class cache to prevent
 * from redeclaration in early cache lookup phase
 * (see https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/issues/242).
 */
class RemoveClassesFromCachePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('fos_http_cache.invalidation.enabled')) {
            return;
        }

        $frameworkExt = $container->getExtension('framework');
        $classes = $frameworkExt->getClassesToCompile();

        // remove classes
        $classesToRemove = array(
            'Symfony\\Component\\EventDispatcher\\Event',
            'Symfony\\Component\\HttpKernel\\Event\\KernelEvent',
            'Symfony\\Component\\HttpKernel\\Event\\FilterControllerEvent',
            'Symfony\\Component\\HttpKernel\\Event\\FilterResponseEvent',
            'Symfony\\Component\\HttpKernel\\Event\\GetResponseEvent',
            'Symfony\\Component\\HttpKernel\\Event\\GetResponseForControllerResultEvent',
            'Symfony\\Component\\HttpKernel\\Event\\GetResponseForExceptionEvent',
        );
        foreach ($classesToRemove as $className) {
            $index = array_search($className, $classes);
            array_splice($classes, $index, 1);
        }

        // overwrite array for class cache via reflection (no other way)
        $reflClass = new \ReflectionClass('Symfony\\Component\\HttpKernel\\DependencyInjection\\Extension');
        $reflProp = $reflClass->getProperty('classes');
        $reflProp->setAccessible(true);
        $reflProp->setValue($frameworkExt, $classes);
    }
}