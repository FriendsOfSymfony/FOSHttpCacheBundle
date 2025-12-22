<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.event_listener.attributes', \FOS\HttpCacheBundle\EventListener\AttributesListener::class)
        ->args([service('controller_resolver')])
        ->tag('kernel.event_subscriber');
};
