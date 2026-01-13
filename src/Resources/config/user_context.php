<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.user_context.hash_generator', \FOS\HttpCache\UserContext\DefaultHashGenerator::class);

    $services->set('fos_http_cache.user_context.request_matcher', \FOS\HttpCacheBundle\UserContext\RequestMatcher::class)
        ->args([
            '',
            '',
        ]);

    $services->set('fos_http_cache.event_listener.user_context', \FOS\HttpCacheBundle\EventListener\UserContextListener::class)
        ->args([
            service('fos_http_cache.user_context.request_matcher'),
            service('fos_http_cache.user_context.hash_generator'),
            service('fos_http_cache.user_context.anonymous_request_matcher'),
            service('fos_http_cache.http.symfony_response_tagger')->ignoreOnInvalid(),
            '%fos_http_cache.event_listener.user_context.options%',
            true,
        ])
        ->tag('kernel.event_subscriber');

    $services->set('fos_http_cache.user_context.role_provider', \FOS\HttpCacheBundle\UserContext\RoleProvider::class)
        ->abstract()
        ->args([service('security.token_storage')->ignoreOnInvalid()]);

    $services->set('fos_http_cache.user_context_invalidator', \FOS\HttpCacheBundle\UserContextInvalidator::class)
        ->args([service('fos_http_cache.default_proxy_client')]);

    $services->set('fos_http_cache.user_context.session_logout_handler', \FOS\HttpCacheBundle\Security\Http\Logout\ContextInvalidationSessionLogoutHandler::class)
        ->private()
        ->args([service('fos_http_cache.user_context_invalidator')]);

    $services->set('fos_http_cache.user_context.switch_user_listener', \FOS\HttpCacheBundle\EventListener\SwitchUserListener::class)
        ->private()
        ->args([service('fos_http_cache.user_context_invalidator')])
        ->tag('kernel.event_subscriber');

    $services->set('fos_http_cache.user_context.anonymous_request_matcher', \FOS\HttpCache\UserContext\AnonymousRequestMatcher::class)
        ->args([[]]);
};
