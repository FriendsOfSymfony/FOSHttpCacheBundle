<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('fos_http_cache.request_matcher', \Symfony\Component\HttpFoundation\ChainRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.request_matcher.path', \Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.request_matcher.host', \Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.request_matcher.methods', \Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.request_matcher.ips', \Symfony\Component\HttpFoundation\RequestMatcher\IpsRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.request_matcher.attributes', \Symfony\Component\HttpFoundation\RequestMatcher\AttributesRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.request_matcher.query_string', \FOS\HttpCacheBundle\Http\RequestMatcher\QueryStringRequestMatcher::class)
        ->private()
        ->abstract();

    $services->set('fos_http_cache.rule_matcher', \FOS\HttpCacheBundle\Http\RuleMatcher::class)
        ->private()
        ->abstract()
        ->args([
            '',
            '',
        ]);

    $services->set('fos_http_cache.rule_matcher.cacheable', \FOS\HttpCacheBundle\Http\RuleMatcher::class)
        ->private()
        ->args([
            service('fos_http_cache.request_matcher.cacheable'),
            service('fos_http_cache.response_matcher.cacheable'),
        ]);

    $services->set('fos_http_cache.rule_matcher.must_invalidate', \FOS\HttpCacheBundle\Http\RuleMatcher::class)
        ->private()
        ->args([
            service('fos_http_cache.request_matcher.unsafe'),
            service('fos_http_cache.response_matcher.non_error'),
        ]);

    $services->set('fos_http_cache.request_matcher.cacheable', \FOS\HttpCacheBundle\Http\RequestMatcher\CacheableRequestMatcher::class)
        ->private();

    $services->set('fos_http_cache.request_matcher.unsafe', \FOS\HttpCacheBundle\Http\RequestMatcher\UnsafeRequestMatcher::class)
        ->private();

    $services->set('fos_http_cache.response_matcher.cacheable', \FOS\HttpCacheBundle\Http\ResponseMatcher\CacheableResponseMatcher::class)
        ->private()
        ->args(['%fos_http_cache.cacheable.response.additional_status%']);

    $services->set('fos_http_cache.response_matcher.non_error', \FOS\HttpCacheBundle\Http\ResponseMatcher\NonErrorResponseMatcher::class)
        ->private();
};
