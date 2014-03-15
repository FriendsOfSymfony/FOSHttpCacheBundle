The Cache Manager
=================

Use the CacheManager to explicitly invalidate or refresh paths, URLs, routes or
headers.

Invalidating just makes the cache fetch the content from the backend when next
time requested, while refresh reloads the content right away. But while `purge`
removes all variants (according to the VARY header), `refresh` will only use
the variant of the refresh request. Both will remove caches for the requested
page for all query string permutations.

* [Invalidating](#invalidating)
* [Refreshing](#refreshing)
* [Invalidating with a Regular Expression](#invalidating-with-a-regular-expression)
* [Tags](#tags)
* [Flushing](#flushing)

Invalidating
------------

Make sure to configure your proxy for purging first.
(See [varnish](https://github.com/FriendOfSymfony/FOSHttpCache/blob/master/doc/varnish.md#purge).)

Invalidate a path:

```php
$cacheManager = $container->get('fos_http_cache.cache_manager');
$cacheManager->invalidatePath('/users');
```

Invalidate an URL:
```php
$cacheManager = $container->get('fos_http_cache.cache_manager');
$cacheManager->invalidatePath('http://www.example.com/users');
```

Invalidate a route:

```php
$cacheManager = $container->get('fos_http_cache.cache_manager');
$cacheManager->invalidateRoute('user_details', array('id' => 123));
```

The cache manager offers a fluent interface:

```php
$cacheManager
    ->invalidateRoute('villains_index')
    ->invalidatePath('/bad/guys')
    ->invalidateRoute('villain_details', array('name' => 'Jaws')
    ->invalidateRoute('villain_details', array('name' => 'Goldfinger')
    ->invalidateRoute('villain_details', array('name' => 'Dr. No');
```

Refreshing
----------

Make sure to configure your proxy for refreshing first.
(See [varnish](https://github.com/FriendOfSymfony/FOSHttpCache/blob/master/doc/varnish.md#refresh).)

Refresh a path:

```php
$cacheManager = $container->get('fos_http_cache.cache_manager');
$cacheManager->refreshPath('/users');
```

Refresh an URL:

```php
$cacheManager = $container->get('fos_http_cache.cache_manager');
$cacheManager->refreshPath('http://www.example.com/users');
```

Refresh a Route:

```php
$cacheManager = $container->get('fos_http_cache.cache_manager');
$cacheManager->refreshRoute('user_details', array('id' => 123));
```

Invalidating with a Regular Expression
--------------------------------------

TODO: this is not implemented yet
https://github.com/FriendOfSymfony/FOSHttpCache/blob/master/doc/varnish.md#ban


Tags
----

Tags can be an efficient way to invalidate whole ranges of content without
needing to figure out the exact URLs.

### Caching Proxy Configuration

You need to configure your caching proxy to support cache tagging. For Varnish,
you can find an example configuration in the [Varnish chapter of the FOSHttpCache library]
(https://github.com/FriendOfSymfony/FOSHttpCache/blob/master/doc/varnish.md#tagging)

### Tag Responses

Use the Cache Manager to tag responses:

```php
// $response is a \Symfony\Component\HttpFoundation\Request object
$cacheManager->tagResponse($response, array('some-tag', 'other-tag'));
```

The tags are appended to already existing tags, unless you set the $replace
option to true:

```php
// $response is a \Symfony\Component\HttpFoundation\Request object
$cacheManager->tagResponse($response, array('different'), true);
```

### Invalidate Tags

And then invalidate cache tags:

```php
$cacheManager->invalidateTags(array('some-tag', 'other-tag'));
```

See the [Tagging](tagging.md) chapter for more information on tagging.

Flushing
--------

Internally, the invalidation requests are queued and only sent out to your HTTP
proxy when the manager is flushed. The manager is flushed automatically at the
right moment:

* when handling a HTTP request, after the response has been sent to the client
  (Symfony’s [kernel.terminate event](http://symfony.com/doc/current/components/http_kernel/introduction.html#the-kernel-terminate-event))
* when running a console command, after the command has finished (Symfony’s
  [console.terminate event](http://symfony.com/doc/current/components/console/events.html#the-consoleevents-terminate-event)).

You can also [flush the cache manager manually](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/cache-invalidator.md#flushing).