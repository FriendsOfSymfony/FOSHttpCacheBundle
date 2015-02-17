cache_manager
=============

The cache manager is the primary interface to invalidate caches. It is enabled
by default if a :doc:`Proxy Client <proxy-client>` is configured.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_manager:
            enabled: true
            generate_url_type: true

``enabled``
-----------

**type**: ``enum`` **options**: ``auto``, ``true``, ``false``

Whether the cache manager service should be enabled. By default, it is enabled
if a proxy client is configured. It can not be enabled without a proxy client.

``generate_url_type``
---------------------

**type**: ``enum`` **options**: ``auto``, ``true``, ``false``, ``relative``, ``network``

The ``$referenceType`` to be used when generating URLs in the ``invalidateRoute()``
and ``refreshRoute()`` calls. True results in absolute URLs including the current domain,
``false`` generates a path without domain, needing a ``base_url`` to be configured
on the proxy client. When set to ``auto``, the value is determined based on ``base_url``
of the default proxy client.
