cache_manager
=============

The cache manager is the primary interface to invalidate caches. It is enabled
by default if a :doc:`Proxy Client <proxy-client>` is configured or when you
specify the ``custom_proxy_client`` field.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_manager:
            enabled: true
            custom_proxy_client: ~
            generate_url_type: true

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
your own service that implements ``FOS\HttpCache\ProxyClientInterface``.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_manager:
            custom_proxy_client: acme.caching.proxy_client

When you specify a custom proxy client, the bundle does not know about the
capabilities of the client. The ``generate_url_type`` defaults to true and
:doc:`tag support <tags>` is only active if explicitly enabled.

``generate_url_type``
---------------------

**type**: ``enum`` **Symfony 2 options**: ``auto`` or one of the constants in UrlGeneratorInterface

The ``$referenceType`` to be used when generating URLs in the ``invalidateRoute()``
and ``refreshRoute()`` calls. If you use ``ABSOLUTE_PATH`` to only generate
paths, you need to configure the ``base_url`` on the proxy client. When set to
``auto``, the value is determined based on whether ``base_url`` is set on the
default proxy client.
