FOSHttpCacheBundle
==================
[![Build Status](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/workflows/CI/badge.svg)](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCacheBundle/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/friendsofsymfony/http-cache-bundle/v/stable.svg)](https://packagist.org/packages/friendsofsymfony/http-cache-bundle)
[![Documentation Status](https://readthedocs.org/projects/foshttpcachebundle/badge/?version=latest)](http://foshttpcachebundle.readthedocs.io/en/latest/?badge=latest)

Introduction
------------

This bundle offers tools to improve HTTP caching with Symfony. It provides
global configuration options to set caching headers based on the path,
controller and other aspects of the request. In addition, it provides services
for the [FOSHttpCache library](https://github.com/FriendsOfSymfony/FOSHttpCache) 
tools to actively invalidate caching proxies and
some additional tools that can help when working with a caching proxy.

Features
--------

* Set path-based cache expiration headers via your app configuration;
* Set up an invalidation scheme without writing PHP code;
* Tag your responses and invalidate cache based on tags;
* Send invalidation requests with minimal impact on performance;
* Differentiate caches based on user *type* (e.g. roles);
* Easily implement your own HTTP cache client.

Documentation
-------------

Documentation is available at [Read the Docs](http://foshttpcachebundle.readthedocs.org/).

Roadmap
-------

This bundle is fully functional with Varnish and used in production in several 
systems. Nginx and the Symfony built-in HttpCache only support a part of the features.

See the [GitHub issues](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/issues)
if you are interested in the development of this bundle.

License
-------

This bundle is released under the MIT license. See the included
[LICENSE](LICENSE) file for more information.
