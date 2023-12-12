<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\Configuration\Tag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\Common\Annotations\AnnotationReader;

class AnnotationListener implements EventSubscriberInterface
{
    public function onKernelController(ControllerEvent $event): void
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $object = new \ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);

        $annotationReader = new AnnotationReader();
        $annotations = $annotationReader->getMethodAnnotations($method);

        //$event->getRequest()->attributes->add();

        $invalidRoute = [];
        $invalidPath = [];
        $tags = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof InvalidateRoute) {
                $invalidRoute[] = $annotation;
            } elseif ($annotation instanceof InvalidatePath) {
                $invalidPath[] = $annotation;
            }
            elseif($annotation instanceof Tag) {
                $tags[] = $annotation;
            }
        }
        if (count($invalidRoute)) {
            $event->getRequest()->attributes->add(['_invalid_route' => $invalidRoute]);
        }
        if (count($invalidPath)) {
            $event->getRequest()->attributes->add(['_invalid_path' => $invalidPath]);
        }
        if (count($tags)) {
            $event->getRequest()->attributes->add(['_tag' => $tags]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
