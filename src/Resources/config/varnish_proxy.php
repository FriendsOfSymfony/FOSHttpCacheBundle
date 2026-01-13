<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.test.proxy_server.varnish', \FOS\HttpCache\Test\Proxy\VarnishProxy::class)
        ->args(['%fos_http_cache.test.proxy_server.varnish.config_file%'])
        ->call('setBinary', ['%fos_http_cache.test.proxy_server.varnish.binary%'])
        ->call('setPort', ['%fos_http_cache.test.proxy_server.varnish.port%'])
        ->call('setIp', ['%fos_http_cache.test.proxy_server.varnish.ip%']);
};
