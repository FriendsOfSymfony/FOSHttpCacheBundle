<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.proxy_client.noop', \FOS\HttpCache\ProxyClient\Noop::class)
        ->public();
};
