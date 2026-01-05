<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.tag_handler.header_formatter', \FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter::class)
        ->private()
        ->args([
            '%fos_http_cache.tag_handler.response_header%',
            '%fos_http_cache.tag_handler.separator%',
        ]);

    $services->set('fos_http_cache.http.symfony_response_tagger', \FOS\HttpCacheBundle\Http\SymfonyResponseTagger::class)
        ->public()
        ->args([['header_formatter' => service('fos_http_cache.tag_handler.header_formatter'), 'strict' => '%fos_http_cache.tag_handler.strict%']]);

    $services->alias(\FOS\HttpCacheBundle\Http\SymfonyResponseTagger::class, 'fos_http_cache.http.symfony_response_tagger')
        ->public();

    $services->alias(\FOS\HttpCache\ResponseTagger::class, 'fos_http_cache.http.symfony_response_tagger')
        ->public();

    $services->set('fos_http_cache.event_listener.tag', \FOS\HttpCacheBundle\EventListener\TagListener::class)
        ->args([
            service('fos_http_cache.cache_manager'),
            service('fos_http_cache.http.symfony_response_tagger'),
            service('fos_http_cache.rule_matcher.cacheable'),
            service('fos_http_cache.rule_matcher.must_invalidate'),
            service('fos_http_cache.tag_handler.expression_language')->ignoreOnInvalid(),
        ])
        ->tag('kernel.event_subscriber');
};
