<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.proxy_client.symfony', \FOS\HttpCache\ProxyClient\Symfony::class)
        ->public()
        ->args([
            service('fos_http_cache.proxy_client.symfony.http_dispatcher'),
            '%fos_http_cache.proxy_client.symfony.options%',
            '',
            '',
        ]);
};
