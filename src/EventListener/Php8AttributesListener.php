<?php

namespace FOS\HttpCacheBundle\EventListener;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

if (Kernel::MAJOR_VERSION >= 5) {
    class_alias(RequestEvent::class, 'FOS\HttpCacheBundle\EventListener\AttributeRequestEvent');
} else {
    class_alias(GetResponseEvent::class, 'FOS\HttpCacheBundle\EventListener\AttributeRequestEvent');
}

/**
 * On kernel.request event, this event handler fetch PHP8 attributes.
 * It is available from PHP 8.0.0.
 *
 * @author Yoann Chocteau <yoann@kezaweb.fr>
 */
class Php8AttributesListener implements EventSubscriberInterface
{
    /**
     * @var ControllerResolverInterface
     */
    private $controllerResolver;

    public function __construct(ControllerResolverInterface $controllerResolver)
    {
        if (\PHP_VERSION_ID < 80000) {
            throw new \Exception(sprintf('Php8AttributesListener must not be loaded for PHP %s', phpversion()));
        }
        $this->controllerResolver = $controllerResolver;
    }

    public function onKernelRequest(AttributeRequestEvent $event)
    {
        $request = $event->getRequest();
        $controller = $this->controllerResolver->getController($request);

        if (!is_array($controller) || 2 !== count($controller)) {
            return;
        }

        $class = new \ReflectionClass($controller[0]);
        $method = $class->getMethod($controller[1]);
        $attributes = [];
        $addAttributes = function ($instance) use (&$attributes) {
            if (
                $instance instanceof ConfigurationInterface &&
                in_array(
                    $instance->getAliasName(), [
                    'tag', 'invalidate_path', 'invalidate_route',
                ])
            ) {
                $attributes['_'.$instance->getAliasName()][] = $instance;
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
