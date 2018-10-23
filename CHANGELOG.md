Changelog
=========

2.6.0
-----

### Changed

* User context lookup now tags the hash lookup response. The logout listener can now invalidate that tag instead of
  doing a BAN request. The previous varnish BAN request has been incorrect and banned all cache entries on Varnish.
  The logout handler is now also activated by default for the Symfony HttpCache in addition to Varnish and Noop.

### Fixed

* Cache Tagging: It is now possible to use cache tagging without installing the
  ``SensioFrameworkExtraBundle``. There is a new configuration option
  ``tags.annotations.enabled`` that can be set to ``false``.

2.5.1
-----

### Fixed

* Cache Tagging: Clear the SymfonyResponseTagger after we tagged a response.
  Usually PHP uses a new instance for every request. But for example the hash
  lookup when using Symfony HttpCache does two requests in the same PHP
  process.

2.5.0
-----

### Added

* New Feature: Support for the max_header_value_length option to split huge tag lists into multiple headers. #483

### Fixed

* Cache control on Symfony 4.1 now also works when the Vary header for user_context_hash is already present on the response. #485

2.4.1
-----

* Adjust session_listener to work with Symfony 3.4.12 (https://github.com/symfony/symfony/pull/27467).

2.4.0
-----

### Added

* Support for the Varnish xkey vmod for more efficient cache tagging.

* Autoconfigure support for custom context providers.

* Autowiring support for the services in this bundle:

  - fos_http_cache.cache_manager => FOS\HttpCacheBundle\CacheManager
  - fos_http_cache.http.symfony_response_tagger => FOS\HttpCacheBundle\Http\SymfonyResponseTagger
  - fos_http_cache.event_listener.cache_control => FOS\HttpCacheBundle\EventListener\CacheControlListener
  - fos_http_cache.proxy_client.default => FOS\HttpCache\ProxyClient\ProxyClient

  The old service names are still available, but using them directly is deprecated.

2.3.1
-----

### Fixed

* Regression in the configuration when you explicitly specified the `default`
  proxy client. This started to be reported as error in 2.3.0 and now works
  again.

2.3.0
-----

### Added

* [Symfony HttpCache] You can now configure the Symfony proxy client to
  directly call the `HttpCache` for invalidation requests instead of executing
  real web requests.
  Use the new configuration option `proxy_client.symfony.use_kernel_dispatcher`
  and follow the instructions in FOSHttpCache to adjust your kernel and
  bootstrap things accordingly.

2.2.2
-----

### Fixed

* Fix `session_listener` decoration when session is not enabled.

2.2.1
-----

### Fixed

* Adjust user context listener to handle Symfony 4.1 breaking behaviour change.

2.2.0
-----

Support for Symfony 4. (Note that only the `fos_http_cache.cache_manager`
service is public in Symfony 4. Use dependency injection if you need direct
access to other services.)

### Added

* You can now use cache tags and invalidate them with the Symfony `HttpCache`
  reverse proxy. You can tweak configuration in the `proxy_client.symfony` 
  section of the configuration. See the FOSHttpCache documentation for
  instructions on how to set up the cache.

* Allow to configure the purge method for the Symfony proxy client.

* You can now also match requests with regular expressions on the query string.
  The new option `match.query_string` is available for cache control rules, tags
  and invalidation.

* ETags can now be false, strong or weak by setting `headers.etag` option to
  `"strong"` or `"weak"` respectively.
  Value `true` due to backward compatibility will be resolved as `"strong"`.

### Fixed

* The FlashMessageListener has been broken during refactoring for 2.0 and now
  works again. Constructor uses an options array.

* Tag annotations now work with SensioFrameworkExtraBundle 4. An accidental
  exception prevents using them with FOSHttpCacheBundle 2.0 and 2.1.

* User context is more reliable not cache when the hash mismatches. (E.g. after
  login/logout.)

* The `ContextInvalidationLogoutHandler` has been deprecated in favor of the
  `ContextInvalidationSessionLogoutHandler`. The original handler was called
  after the invalidation of the session, and thus did not invalidate the session
  it should have but a newly created one. You should remove the deprecated service
  `fos_http_cache.user_context.logout_handler` from the logout.handlers section
  of your firewall configuration.

* User context compatibility which was broken due to Symfony making responses
  private if the session is started as of Symfony 3.4+.

### Deprecated

* Setting up custom services with the commands provided in this bundle has been
  deprecated. The `$commandName` constructor argument will be removed in 3.0.

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

1.3.16
------

* Adjust session_listener to work with Symfony 3.4.12 (https://github.com/symfony/symfony/pull/27467).

1.3.15
------

* Fix session_listener decoration when session is not enabled.

1.3.14
------

* User context compatibility which was broken due to Symfony making responses
  private if the session is started as of Symfony 3.4+.

1.3.13
------

* Symfony HttpCache User Context: Move the AnonymousRequestMatcher to FOSHttpCache.

  The recommended way to ignore cookie based sessions is to set `session_name_prefix` to
  false rather than omit the Cookie header from `user_identifier_headers`.

1.3.12
------

* Prevent potential accidental caching on user context hash mismatch (particularly with symfony HttpCache).

1.3.11
------

* #395 : Compatibility with SensioFrameworkExtraBundle 4.

1.3.10
------

* Avoid calling deprecated method in Symfony 3.2.

1.3.9
-----

* Fix configuration handling when only custom proxy client is configured.

1.3.8
-----

* Do not sanity check hash on anonymous requests.

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
