<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.event_listener.invalidation', \FOS\HttpCacheBundle\EventListener\InvalidationListener::class)
        ->args([
            service('fos_http_cache.cache_manager'),
            service('router'),
            service('fos_http_cache.rule_matcher.must_invalidate'),
            service('fos_http_cache.invalidation.expression_language')->ignoreOnInvalid(),
        ])
        ->tag('kernel.event_subscriber');
};
