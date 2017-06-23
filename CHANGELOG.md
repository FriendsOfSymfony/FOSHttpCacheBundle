Changelog
=========

2.1.0
-----

### Added

* Individual rules in the `cache_control` can now again have a `match_response`
  or `additional_response_status` configuration to limit the rule to certain
  responses.

  For this, the signature of CacheControlListener::addRule had to be changed.
  It now expects a RuleMatcherInterface instead of the
  ResponseMatcherInterface. If you extended the listener or change the service
  configuration, this could be a **BC BREAK** for your application.

### Fixed

* If no response matching is configured on `cache_control`, the global
  `cacheable` configuration is now respected to decide whether cache headers
  should be set. By default, this follows RFC 7234, only responses with status
  200, 203, 204, 206, 300,  301, 404, 405, 410, 414 or 501 get cache headers.

  We decided to consider this a bugfix, but if your relied on this behaviour it
  will be a **BC BREAK** for your application.

2.0.0
-----

### General 

* Updated the version of FOSHttpCache to 2.0.0. See the [FOSHttpCache changelog](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/CHANGELOG.md) 
  for more information. Most importantly, we removed the hard coupling on the
  Guzzle HTTP client (using HTTPlug). Your composer.json now needs to
  specify which HTTP client to install; see the [installation instructions](http://foshttpcachebundle.readthedocs.io/en/latest/overview.html#installation)
* Deprecated methods have been removed.

### Proxy client

* The configuration for the proxy client has been adjusted. Proxy servers are 
  now configured under `http` and `servers` must be a list - a comma separated 
  string of server IPs is no longer supported.

### Event listeners

* **BC break:** the `UserContextListener` constructor signature was changed to
  take an array of options.
* **BC break:** renamed the event listener classes to `XyzListener`.

### Rule matcher

* **BC break:** The `match_response` and `additional_cacheable_status` 
  configuration parameters were removed for individual match rules.
  Note that `match_response` and `additional_response_status` have been re-added for
  `cache_control` in 2.0.1.

* **BC break:** The second argument of the `RuleMatcher` constructor was changed 
  to take a `ResponseMatcherInterface`.
  
* Cacheable status codes are now configured globally 
  (`cacheable.response.additional_status` or `cacheable.response.expression`).
  
### Tags
  
* **BC break:** The TagHandler has been split. Invalidating tags happens through
  CacheManager::invalidateTags (if you use annotations for tag invalidation, you 
  don't need to change anything). Recording tags and writing them into the 
  responses is now done through the SymfonyResponseTagger.
  The service `fos_http_cache.handler.tag_handler` no longer exists. For
  tagging responses, use `fos_http_cache.http.symfony_response_tagger` instead,
  and to invalidate tags use the service `fos_http_cache.cache_manager`.
* **BC break:** The configuration `tags.header` has been removed. Configuring
  the header for tagging responses is now done at `tags.response_header`.
  Configuring the header for tag invalidation requests is now done at
  `proxy_client.varnish.tags_header`.
  
### Tests

* **BC break:** Dropped the proxy client services as they where not used anywhere. The
  services `fos_http_cache.test.client.varnish` and 
  `fos_http_cache.test.client.nginx` no longer exist.
  
### User context

* Added an option `always_vary_on_context_hash` to make it possible to disable 
  automatically setting the vary headers for the user hash.

1.3.7
-----

* Add a sanity check on UserContextHash to avoid invalid content being cached
  (example: anonymous cache set for authenticated user). This scenario occures
  when the UserContextHash is cached by Varnish via 
  `fos_http_cache.user_context.hash_cache_ttl` > 0 and the session is lost via 
  garbage collector. The data given is the anonymous one despite having a hash 
  for authenticated, all authenticated users will then have the anonymous version.
  Same problem could occurs with users having is role changed or anything else
  that can modify the hash.

1.3.2
-----

* Fixed some status codes (such as 204 and 302) not triggering invalidation.

1.3.1
-----

* Fixed configuration handling with symfony and nginx cache client. Cache
  tagging is now immediately reported to not work with those clients.

1.3.0
-----

* Added configuration for Symfony HttpCache client and HttpCache now loads
  purge and refresh handlers by default.
* Configured/annotated cache tags on subrequests (in Twig: `render(controller())`)
  are no longer ignored. Additionally, it is now possible to add tags from code
  before the response object has been created, by using the TagHandler, and from
  Twig with the `fos_httpcache_tag` function.
  If you defined custom services for the `InvalidateTagCommand`, you should
  now inject the TagHandler instead of the CacheManager.
* **deprecated** `CacheManager::tagResponse` in favor of `TagHandler::addTags`
* Added configuration option for custom proxy client (#208)
* Added support for a simple Etag header in the header configuration (#207)

1.2.0
-----

* Refactored the Symfony built-in HttpCache support to be extensible.
  `FOS\HttpCacheBundle\HttpCache` is deprecated in favor of `EventDispatchingHttpCache`.

  BC break: If you overwrite cleanupForwardRequest in your cache kernel, you need to
  extend FOS\HttpCache\SymfonyCache\UserContextSubscriber and move that logic to the
  method cleanupHashLookupRequest in there.

1.1.0
-----

* Allow cache headers overwrite.
* Added support for the user context lookup with Symfony built-in reverse
  proxy, aka `HttpCache`.

1.0.0
-----

Initial release. To migrate from other Symfony2 cache bundles, see
[LiipCacheControlBundle](https://github.com/liip/LiipCacheControlBundle) or
[DriebitHttpCacheBundle](https://github.com/driebit/DriebitHttpCacheBundle).
