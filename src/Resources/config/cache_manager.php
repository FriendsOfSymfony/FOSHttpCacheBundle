<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('fos_http_cache.cache_manager', \FOS\HttpCacheBundle\CacheManager::class)
        ->public()
        ->args([
            service('fos_http_cache.default_proxy_client'),
            service('router'),
        ])
        ->call('setEventDispatcher', [service('event_dispatcher')->ignoreOnInvalid()])
        ->call('setGenerateUrlType', ['%fos_http_cache.cache_manager.generate_url_type%']);

    $services->alias(\FOS\HttpCacheBundle\CacheManager::class, 'fos_http_cache.cache_manager')
        ->public();

    $services->set('fos_http_cache.event_listener.log', \FOS\HttpCache\EventListener\LogListener::class)
        ->abstract()
        ->args([service('logger')]);
};
