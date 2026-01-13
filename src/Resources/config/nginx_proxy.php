<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.test.proxy_server.nginx', \FOS\HttpCache\Test\Proxy\NginxProxy::class)
        ->args(['%fos_http_cache.test.proxy_server.nginx.config_file%'])
        ->call('setBinary', ['%fos_http_cache.test.proxy_server.nginx.binary%'])
        ->call('setPort', ['%fos_http_cache.test.proxy_server.nginx.port%'])
        ->call('setIp', ['%fos_http_cache.test.proxy_server.nginx.ip%']);
};
