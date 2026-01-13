<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.proxy_client.varnish', \FOS\HttpCache\ProxyClient\Varnish::class)
        ->public()
        ->args([
            service('fos_http_cache.proxy_client.varnish.http_dispatcher'),
            '%fos_http_cache.proxy_client.varnish.options%',
            '',
            '',
        ]);
};
