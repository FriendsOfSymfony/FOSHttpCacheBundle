FOSHttpCacheBundle
==================

This is a work in progress to unite the LiipCacheControlBundle and
DriebitHttpCacheBundle into one good bundle. We
[hope to publish this as a FOS bundle](https://github.com/FriendsOfSymfony/friendsofsymfony.github.com/issues/42)

Introduction
------------

This bundle offers tools to improve HTTP caching with Symfony2. It provides
global configuration options to set caching headers based on the path,
controller and other aspects of the request. It provides means to actively
invalidate caching proxies and some additional tools that can help when working
with a caching proxy.

Features
--------

### Caching Headers

### Cache Invalidation

* Set up an invalidation scheme without writing PHP code.
* Send invalidation requests with minimal impact on performance.
* Easily implement your own HTTP cache client.

### Tools

Documentation
-------------

Documentation is included in the [Resources/doc](Resources/doc/index.md) directory.

License
-------

This bundle is released under the MIT license. See the included [LICENSE](LICENSE) file for more information.
