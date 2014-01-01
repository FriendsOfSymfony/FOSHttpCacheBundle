The Cache Manager
=================

Use the [cache manager](/CacheManager.php) to explicitly invalidate or refresh
paths, routes, URLs or headers.

* [Invalidating paths and URLs](#invalidating-paths-and-urls)
* [Refreshing paths and URLs](#refreshing-paths-and-urls)
* [Tags](#tags)
* [Flushing](#flushing)

Invalidating paths and URLs
---------------------------

Make sure to [configure your proxy for purging](varnish.md#purge) first.

Invalidate a path:

```php
$cacheManager = $container->get('fos_http_cache.cache_manager');

$cacheManager->invalidatePath('/users');
```

Invalidate a route:

```php
$cacheManager->invalidateRoute('user_details', array('id' => 123));
```

Refreshing paths and URLs
-------------------------

Make sure to [configure your proxy for refreshing](varnish.md#refresh) first.

Refresh a path and a route:

```php
$cacheManager->refreshPath('/')
    ->refreshRoute('villains_index');
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

Tags
----

Make sure to [configure your proxy for tagging](varnish.md#tagging) first.

Use the Cache Manager to tag responses:

```php
// $response is a \Symfony\Component\HttpFoundation\Request object
$cacheManager->tagResponse($response, array('some-tag', 'other-tag'));
```

And then invalidate cache tags:

```php
$cacheManager->invalidateTags(array('some-tag', 'other-tag'));
```

See the [Tagging](tagging.md) chapter for more information on tagging.

Flushing
--------

Internally, the invalidation requests are queued and only sent out to your HTTP
proxy when the manager is flushed. During HTTP requests, the manager is flushed
automatically. If you want to invalidate objects outside request context, for
instance from the command-line, you need to flush the cache manager manually:

```php
$cacheManager
    ->invalidateRoute(...)
    ->invalidatePath(...)
    ->flush();
```

The performance impact of sending invalidation requests is kept to a minimum by:

* flushing the cache manager only after the response by your controller has been sent to the client’s browser
(during Symfony’s [kernel.terminate event](http://symfony.com/doc/current/components/http_kernel/introduction.html#the-kernel-terminate-event)).
* sending all invalidation requests in parallel.