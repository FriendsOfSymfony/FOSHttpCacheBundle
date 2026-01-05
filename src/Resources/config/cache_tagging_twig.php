<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.twig.tag_extension', \FOS\HttpCacheBundle\Twig\CacheTagExtension::class)
        ->args([service('fos_http_cache.http.symfony_response_tagger')])
        ->tag('twig.extension');
};
