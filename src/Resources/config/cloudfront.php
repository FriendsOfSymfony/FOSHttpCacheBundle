<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.proxy_client.cloudfront', \JeanBeru\HttpCacheCloudFront\Proxy\CloudFront::class)
        ->public()
        ->args([
            service('fos_http_cache.proxy_client.cloudfront.cloudfront_client'),
            '%fos_http_cache.proxy_client.cloudfront.options%',
        ]);
};
