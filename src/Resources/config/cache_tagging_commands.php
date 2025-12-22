<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.command.invalidate_tag', \FOS\HttpCacheBundle\Command\InvalidateTagCommand::class)
        ->args([service('fos_http_cache.cache_manager')])
        ->tag('console.command', ['command' => 'fos:httpcache:invalidate:tag']);
};
