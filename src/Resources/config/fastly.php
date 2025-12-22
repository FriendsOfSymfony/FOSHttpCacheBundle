<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.proxy_client.fastly', \FOS\HttpCache\ProxyClient\Fastly::class)
        ->private()
        ->lazy()
        ->args([
            service('fos_http_cache.proxy_client.fastly.http_dispatcher'),
            '%fos_http_cache.proxy_client.fastly.options%',
            '',
            '',
        ]);
};
