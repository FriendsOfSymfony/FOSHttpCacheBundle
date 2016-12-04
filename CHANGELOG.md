Changelog
=========

2.0.0 (unreleased)
------------------

* [Proxy Client Configuration] The configuration for the proxy client has been
  adjusted. Proxy servers are now configured under `http` and `servers` must be
  a list - a comma separated string of server IPs is no longer supported.

* [User Context] Added an option always_vary_on_context_hash to make it
  possible to disable automatically setting the vary headers for the user
  hash.
  
* [Event Listeners] Renamed the event listener classes to XxxLlistener.

* Updated the version of FOSHttpCache to 2.*. See [FOSHttpCache changelog]
  (https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/CHANGELOG.md).  
  Most important, there is no more hard coupling on Guzzle HTTP client. We now
  use the HTTPlug HTTP client abstraction. Your composer.json now needs to
  specify which HTTP client to install, see [installation instructions]
  (http://foshttpcachebundle.readthedocs.org/en/stable/installation.html)

* [Tags] The TagHandler has been split. Invalidating tags happens through the
  CacheManager (if you use annotations for tag invalidation, you don't need to
  change anything). Recording tags and writing them into the responses is done
  through the SymfonyResponseTagger now.

* [Test] Dropped the proxy client services as they where not used anywhere. The
  services `fos_http_cache.test.client.varnish` and `fos_http_cache.test.client.nginx`
  no longer exist.

* Deprecated methods have been removed.

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
