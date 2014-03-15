Cache Invalidation from the Commandline
=======================================

This bundle provides commands to trigger cache invalidation from the command
line. You could also send invalidation requests with a command line tool like
curl or, in the case of varnish, varnishadm. But the commands simplify the task
and will automatically talk to all configured cache instances.

* `fos:httpcache:invalidate:path` accepts one or more paths and invalidates
  each of them. See [Invalidating](cache-manager.md#invalidating).
* `fos:httpcache:refresh:path` accepts one or more paths and refreshes each of
  them. See [Refreshing](cache-manager.md#refreshing).
* `fos:httpcache:invalidate:regex` expects a regular expression and invalidates
  all cache entries matching that expression. To invalidate your entire cache,
  you can specify `.` which will match everything. See
  [Invalidating with a Regular Expression](cache-manager.md#invalidating-with-a-regular-expression).
* `fos:httpcache:invalidate:tag` accepts one or more tags and invalidates all
  cache entries matching any of those tags. See [Tags](cache-manager.md#tags).

If you need more complex interaction with the cache manager, the best is to
write your own commands and use the [Cache Manager](cache-manager.md) to implement
your specific logic.
