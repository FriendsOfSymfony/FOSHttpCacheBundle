FOSHttpCacheBundle
==================

This is the documentation for the FOSHttpCacheBundle. It covers:

1. [Installation](installation.md) of the bundle
2. [Configuring Caching Headers](caching-headers-configuration.md) which also works without a caching proxy.
3. Cache Invalidation
   1. [The Cache Manager Service](cache-manager.md)
   2. [Cache Tagging](tagging.md)
   3. [Invalidator Configuration](invalidation-configuration.md)
   4. [Invalidating on the Command Line](invalidation-commandline.md)
4. Event Listeners
   1. [User Context Listener](user-context.md)
   2. [Flash message Listener](flash-message-listener.md)
5. [Varnish Debugging Configuration](varnish-debugging-configuration.md)
6. [Testing](testing.md)

Functionality              | Annotations                                        | Configuration
---------------------------|----------------------------------------------------|-----------------------------------------------
Set Cache-Control headers  | (SensioFrameworkExtraBundle)                       | [rules](caching-headers-configuration.md)
Tagging and invalidating   | [@Tag](tagging.md)                                 | [rules](tagging.md#tagging-with-configuration)
Invalidate routes          | [@InvalidateRoute](annotations.md#invalidateroute) | [invalidators](invalidation-configuration.md)
Invalidate paths           | [@InvalidatePath](annotations.md#invalidatepath)   | -
