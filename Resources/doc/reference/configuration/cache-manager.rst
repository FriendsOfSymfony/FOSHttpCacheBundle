cache_manager
=============

The cache manager is the primary interface to invalidate caches. It is enabled
by default if a :doc:`Proxy Client <proxy-client>` is configured or when you
specify the ``custom_proxy_client`` field.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_manager:
            enabled: auto

``enabled``
-----------

**type**: ``enum`` **options**: ``auto``, ``true``, ``false``

Whether the cache manager service should be enabled. By default, it is enabled
if a proxy client is configured. It can not be enabled without a proxy client.

.. _custom_proxy_client:

``custom_proxy_client``
-----------------------

**type**: ``string``

Instead of configuring a :doc:`Proxy Client <proxy-client>`, you can define
your own service that implements ``FOS\HttpCache\ProxyClient``.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_manager:
            custom_proxy_client: acme.caching.proxy_client

When you specify a custom proxy client, the bundle does not know about the
capabilities of the client. The ``generate_url_type`` defaults to absolute
URL and :doc:`tag support <tags>` is only active if explicitly enabled.
