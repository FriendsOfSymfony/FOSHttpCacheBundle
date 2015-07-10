Changelog
=========

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
