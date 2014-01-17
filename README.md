FOSHttpCacheBundle
==================
[![Build Status](https://travis-ci.org/ddeboer/FOSHttpCacheBundle.png?branch=master)](https://travis-ci.org/ddeboer/FOSHttpCacheBundle)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCacheBundle/badges/quality-score.png?s=023c10bc7c04be6d779bc42884f61a8ad3b17146)](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCacheBundle/)
[![Code Coverage](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCacheBundle/badges/coverage.png?s=f7424d7692b6125f36c9c29d7fd635b01d06c0df)](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCacheBundle/)

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

* Set path-based cache expiration headers via your app configuration.
* Set up an invalidation scheme without writing PHP code.
* Send invalidation requests with minimal impact on performance.
* Easily implement your own HTTP cache client.

Documentation
-------------

Documentation is included in the [Resources/doc](Resources/doc/index.md) directory.

License
-------

This bundle is released under the MIT license. See the included [LICENSE](LICENSE)
file for more information.