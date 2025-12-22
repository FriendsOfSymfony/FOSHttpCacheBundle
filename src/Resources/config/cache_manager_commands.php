<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.command.invalidate_path', \FOS\HttpCacheBundle\Command\InvalidatePathCommand::class)
        ->args([service('fos_http_cache.cache_manager')])
        ->tag('console.command');

    $services->set('fos_http_cache.command.invalidate_regex', \FOS\HttpCacheBundle\Command\InvalidateRegexCommand::class)
        ->args([service('fos_http_cache.cache_manager')])
        ->tag('console.command');

    $services->set('fos_http_cache.command.refresh_path', \FOS\HttpCacheBundle\Command\RefreshPathCommand::class)
        ->args([service('fos_http_cache.cache_manager')])
        ->tag('console.command');

    $services->set('fos_http_cache.command.clear', \FOS\HttpCacheBundle\Command\ClearCommand::class)
        ->args([service('fos_http_cache.cache_manager')])
        ->tag('console.command');
};
