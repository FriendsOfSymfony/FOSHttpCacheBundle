<?php

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use FOS\HttpCacheBundle\Configuration\Tag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * On kernel.request event, this event handler fetches PHP attributes.
 *
 * @author Yoann Chocteau <yoann@kezaweb.fr>
 */
final class AttributesListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ControllerResolverInterface $controllerResolver,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $controller = $this->controllerResolver->getController($request);

        if (true === \is_object($controller)) {
            $class = new \ReflectionClass($controller);
            $method = $class->getMethod('__invoke');
        } elseif (true === \is_array($controller) && 2 === \count($controller)) {
            $class = new \ReflectionClass($controller[0]);
            $method = $class->getMethod($controller[1]);
        } else {
            return;
        }

        $attributes = [];
        $addAttributes = static function ($instance) use (&$attributes) {
            if ($key = match (get_class($instance)) {
                InvalidatePath::class => '_invalidate_path',
                InvalidateRoute::class => '_invalidate_route',
                Tag::class => '_tag',
                default => false,
            }) {
                $attributes[$key][] = $instance;
            }
        };

        foreach ($class->getAttributes() as $classAttribute) {
            $addAttributes($classAttribute->newInstance());
        }
        foreach ($method->getAttributes() as $methodAttribute) {
            $addAttributes($methodAttribute->newInstance());
        }

        foreach ($attributes as $key => $attr) {
            $request->attributes->set(
                $key,
                array_merge($attr, $request->attributes->get($key, []))
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
