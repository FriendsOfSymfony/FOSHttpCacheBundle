<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.event_listener.cache_control', \FOS\HttpCacheBundle\EventListener\CacheControlListener::class)
        ->public()
        ->args([
            '%fos_http_cache.debug_header%',
            '%fos_http_cache.ttl_header%',
        ])
        ->tag('kernel.event_subscriber');

    $services->alias(\FOS\HttpCacheBundle\EventListener\CacheControlListener::class, 'fos_http_cache.event_listener.cache_control')
        ->public();

    $services->set('fos_http_cache.response_matcher.cache_control.cacheable_response', \FOS\HttpCacheBundle\Http\ResponseMatcher\CacheableResponseMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.response_matcher.cache_control.expression', \FOS\HttpCacheBundle\Http\ResponseMatcher\ExpressionResponseMatcher::class)
        ->private()
        ->abstract();
};
