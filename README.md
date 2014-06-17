FOSHttpCacheBundle
==================
[![Build Status](https://travis-ci.org/FriendsOfSymfony/FOSHttpCacheBundle.svg?branch=master)](https://travis-ci.org/FriendsOfSymfony/FOSHttpCacheBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/?branch=master)

Introduction
------------

This bundle offers tools to improve HTTP caching with Symfony2. It provides
global configuration options to set caching headers based on the path,
controller and other aspects of the request. It provides services for the
FOSHttpCache library tools to actively invalidate caching proxies and some
additional tools that can help when working with a caching proxy.

Features
--------

* Set path-based cache expiration headers via your app configuration.
* Set up an invalidation scheme without writing PHP code.
* Send invalidation requests with minimal impact on performance.
* Easily implement your own HTTP cache client.

Documentation
-------------

Documentation is available at [Readthedocs](http://foshttpcachebundle.readthedocs.org/).

License
-------

This bundle is released under the MIT license. See the included
[LICENSE](Resources/meta/LICENSE) file for more information.
