Installation
------------

This library is available on [Packagist](https://packagist.org/packages/driebit/http-cache-bundle). You can install it
using Composer:
```bash
$ composer require driebit/http-cache-bundle:dev-master
```

Then add the bundle to your application:
```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        ...
        new Driebit\HttpCacheBundle\DriebitHttpCacheBundle(),
        ...
    );
}
```

Configuration
-------------

You need to configure at least one HTTP cache proxy. Currently, this bundle offers integration with
[Varnish](https://www.varnish-cache.org/) only.

```yaml
# app/config/config.yml

driebit_http_cache:
  http_cache:
    varnish:
      ips: [ 123.123.123.1, 123.123.123.2 ]
      host: yourwebsite.com
```

Usage
-----

### Invalidation using the cache manager

Use the [cache manager](/CacheManager.php) to invalidate (purge) routes:
```php
$cacheManager = $container->get('driebit_http_cache.cache_manager');
$cacheManager->invalidateRoute('user_details', array('id' => 123));
```

You can invalidate multiple routes together:
```php
$cacheManager
    ->invalidateRoute('villains_index')
    ->invalidateRoute('villain_details', array('name' => 'Jaws')
    ->invalidateRoute('villain_details', array('name' => 'Goldfinger')
    ->invalidateRoute('villain_details', array('name' => 'Dr. No');
```

Internally, the cache manager collects all routes to be invalidated and only sends them when it gets flushed. During
HTTP requests, the manager is flushed automatically. If you want to invalidate routes outside request context, for
instance from the command-line, you need to flush the cache manager manually:
```php
$cacheManager
  ->invalidateRoute(...)
  ->flush();
```

The performance impact of sending invalidation requests is kept to a minimum by:

* flushing the cache manager only after the response by your controller has been sent to the client’s browser
(during Symfony’s [kernel.terminate event](http://symfony.com/doc/current/components/http_kernel/introduction.html#the-kernel-terminate-event)).
* sending all invalidation requests in parallel.

### Invalidation using invalidators

Invalidators offer a second way to invalidate routes, using configuration only. Each invalidator contains:

* one or more `origin_routes`, i.e., routes that trigger the invalidation
* one or more `invalidate_routes`, i.e., routes that will be invalidated.

You can configure invalidators as follows:
```yaml
# app/config/config.yml

driebit_http_cache:
  ...
  invalidators:
    villains:
      origin_routes: [ villain_edit, villain_delete, villain_publish ]
      invalidate_routes:
        villains_index: ~    # e.g., /villains
        villain_details: ~   # e.g., /villain/{id}
    another_invalidator:
      origin_routes: [ ... ]
      invalidate_routes:
        ...
```

Now when a request to either one of the three origin routes returns a 200 response, both `villains_index` and
`villain_details` will be purged.

Assume route `villain_edit` resolves to `/villain/{id}/edit`. When a client successfully edits the details for villain
with id 123 (at `/villain/123/edit`), the index of villains (at `/villains`) can be invalidated (purged) without
trouble. But which villain details page should we purge? The current request parameters are automatically matched
against invalidate route parameters of the same name. In the request to `/villain/123/edit`, the value of the `id`
parameter is `123`. This value is then used as the value for the `id` parameter of the `villain_details` route. In the
end, the page `villain/123` will be purged.

Run the tests
-------------

Clone this repository, install its vendors, and invoke PHPUnit:
```bash
$ composer install --dev
$ phpunit
```