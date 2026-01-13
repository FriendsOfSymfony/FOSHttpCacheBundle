<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.proxy_client.cloudflare', \FOS\HttpCache\ProxyClient\Cloudflare::class)
        ->public()
        ->lazy()
        ->args([
            service('fos_http_cache.proxy_client.cloudflare.http_dispatcher'),
            '%fos_http_cache.proxy_client.cloudflare.options%',
            '',
            '',
        ]);
};
