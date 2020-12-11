<?php


namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

if (Kernel::MAJOR_VERSION >= 5) {
    class_alias(RequestEvent::class, 'FOS\HttpCacheBundle\EventListener\AttributeRequestEvent');
} else {
    class_alias(GetResponseEvent::class, 'FOS\HttpCacheBundle\EventListener\AttributeRequestEvent');
}

class CatchAttributesListener implements EventSubscriberInterface
{
    /**
     * @var ControllerResolver
     */
    private $controllerResolver;

    public function __construct(
        ControllerResolver $controllerResolver
    ) {
        $this->controllerResolver = $controllerResolver;
    }

    public function onKernelRequest(AttributeRequestEvent $event)
    {
        $request = $event->getRequest();

        if (
            method_exists(\ReflectionProperty::class, 'getAttributes') &&
            $controller = $this->controllerResolver->getController($request)
        ) {
            $class = new \ReflectionClass($controller[0]);
            $method = $class->getMethod($controller[1]);
            $attributes = [];
            $addAttributes = function($instance) use (&$attributes) {
                if (
                    $instance instanceof ConfigurationInterface &&
                    in_array(
                        $instance->getAliasName(), [
                        'tag', 'invalidate_path', 'invalidate_route'
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

            foreach($attributes as $key => $attr) {
                $request->attributes->set(
                    $key,
                    array_merge($attr, $request->attributes->get($key, []))
                );
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
