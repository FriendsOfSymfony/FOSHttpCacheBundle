Invalidation
============

**Prerequisites**: :ref:`configure caching proxy <foshttpcache:proxy-configuration>`.

By *invalidating* a piece of content, you tell your HTTP caching proxy (Varnish
or Nginx) to no longer serve it to clients. When next requested, the proxy will
fetch a fresh copy from the backend application and serve that instead. By
*refreshing* a piece of content, a fresh copy will be fetched right away.

.. important::

    In order to invalidate cached objects, requests are sent to your caching proxy.
    So for the following examples to work you must first
    :ref:`configure your proxy <foshttpcache:proxy-configuration>`.

Cache Manager
-------------

To invalidate single paths, URLs and routes manually, use the
``invalidatePath($path)`` and ``invalidateRoute($route, $params)`` methods on
the cache manager::

    $cacheManager = $container->get('fos_http_cache.cache_manager');

    // Invalidate a path
    $cacheManager->invalidatePath('/users')->flush();

    // Invalidate a URL
    $cacheManager->invalidatePath('http://www.example.com/users')->flush();

    // Invalidate a route
    $cacheManager->invalidateRoute('user_details', array('id' => 123))->flush();

To invalidate multiple representations matching a regular expression, call
``invalidateRegex($path, $contentType, $hosts)``::

    $cacheManager->invalidateRegex('.*', 'image/png', array('example.com'));

To refresh paths and routes, you can use ``refreshPath($path)`` and
``refreshRoute($route, $params)`` in a similar manner. See
:doc:`/reference/cache-manager` for more information.

.. _invalidation configuration:

Configuration
-------------

You can add invalidation rules to your application configuration:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        invalidation:
            rules:
                -
                    match:
                        attributes:
                            _route: "villain_edit|villain_delete"
                    routes:
                        villains_index: ~    # e.g., /villains
                        villain_details: ~   # e.g., /villain/{id}

Now when a request to either route ``villain_edit`` or route ``villain_delete``
returns a succesful response, both routes ``vilains_index`` and
``villain_details`` will be purged. See the
:doc:`/reference/configuration/invalidation` configuration reference.

Annotations
-----------

Set the ``@InvalidatePath`` and ``@InvalidateRoute`` annotations to trigger
invalidation from your controllers::

    use FOS\HttpCacheBundle\Configuration\InvalidatePath;

    /**
     * @InvalidatePath("/posts")
     * @InvalidatePath("/posts/latest")
     * @InvalidateRoute("overview", params={"type" = "latest"})")
     */
    public function editAction()
    {
    }

See the :doc:`/reference/annotations` reference.

Console Commands
----------------

This bundle provides commands to trigger cache invalidation from the command
line. You could also send invalidation requests with a command line tool like
curl or, in the case of varnish, varnishadm. But the commands simplify the task
and will automatically talk to all configured cache instances.

* ``fos:httpcache:invalidate:path`` accepts one or more paths and invalidates
  each of them. See :ref:`cache manager invalidation`.
* ``fos:httpcache:refresh:path`` accepts one or more paths and refreshes each of
  them. See :ref:`cache manager refreshing`.
* ``fos:httpcache:invalidate:regex`` expects a regular expression and invalidates
  all cache entries matching that expression. To invalidate your entire cache,
  you can specify ``.`` (dot) which will match everything.
  See :ref:`cache manager invalidation`.
* ``fos:httpcache:invalidate:tag`` accepts one or more tags and invalidates all
  cache entries matching any of those tags. See :doc:`tagging`.

If you need more complex interaction with the cache manager, best write your
own commands and use the :doc:`cache manager </reference/cache-manager>` to implement
your specific logic.
