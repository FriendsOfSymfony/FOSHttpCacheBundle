Invalidation
============

Invalidation with the Cache Manager
-----------------------------------

See the :ref:`Cache Manager chapter <cache manager invalidation>`
on how to manually invalidate content.

.. _invalidation configuration:

Invalidation with Configuration
-------------------------------

In order to invalidate cached objects, requests are sent to your HTTP caching
proxy (for instance, Varnish). So in order to use this bundle’s invalidation
functionality, you will have to
:ref:`configure your HTTP caching proxy first <foshttpcache:proxy-configuration>`.

Each configuration entry contains:

* one or more ``origin_routes``, i.e., routes that trigger the invalidation
* one or more ``invalidate_routes``, i.e., routes that will be invalidated.

You can configure invalidation rules as follows:

.. code-block:: yaml

    # app/config/config.yml

    fos_http_cache:
        invalidation:
            rules:
                -
                    match:
                        attributes:
                            _route: "villain_edit|villain_delete|villain_publish"
                    routes:
                        villains_index: ~    # e.g., /villains
                        villain_details: ~   # e.g., /villain/{id}

Now when a request to either one of the three origin routes returns a 200
response, both ``villains_index`` and ``villain_details`` will be purged.

Assume route ``villain_edit`` resolves to ``/villain/{id}/edit``. When a client
successfully edits the details for villain with id 123 (at
``/villain/123/edit``), the index of villains (at ``/villains``) can be
invalidated (purged) without trouble. But which villain details page should we
purge? The current request parameters are automatically matched against
invalidate route parameters of the same name. In the request to
``/villain/123/edit``, the value of the ``id`` parameter is ``123``. This value
is then used as the value for the `id` parameter of the `villain_details`
route. In the end, the page ``villain/123`` will be purged.

Invalidation with Annotations
-----------------------------

See the :ref:`Annotations reference <invalidatepath>`
on how to invalidate content with annotations.

Invalidation with Console Commands
----------------------------------

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
  you can specify ``.`` which will match everything. See :ref:`cache manager invalidation`.
* ``fos:httpcache:invalidate:tag`` accepts one or more tags and invalidates all
  cache entries matching any of those tags. See :doc:`tagging`.

If you need more complex interaction with the cache manager, best write your
own commands and use the :doc:`cache manager <reference/cache-manager>` to implement
your specific logic.
