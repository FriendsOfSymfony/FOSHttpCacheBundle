Cache Invalidation Configuration
================================

In order to invalidate cached objects, requests are sent to your HTTP caching
proxy (for instance, Varnish). So in order to use this bundle’s invalidation
functionality, you will have to configure your HTTP caching proxy first:

* [Varnish](varnish.md) supports all three invalidation methods.

### Invalidation using invalidators

Invalidators offer an alternative way to tag your content, similar to what
you can do with [annotations](tagging.md).

Each configuration entry contains:

* one or more `origin_routes`, i.e., routes that trigger the invalidation
* one or more `invalidate_routes`, i.e., routes that will be invalidated.

You can configure invalidators as follows:
```yaml
# app/config/config.yml

fos_http_cache:
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
