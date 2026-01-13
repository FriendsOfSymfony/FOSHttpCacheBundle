<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.event_listener.flash_message', \FOS\HttpCacheBundle\EventListener\FlashMessageListener::class)
        ->args(['%fos_http_cache.event_listener.flash_message.options%'])
        ->tag('kernel.event_subscriber');
};
