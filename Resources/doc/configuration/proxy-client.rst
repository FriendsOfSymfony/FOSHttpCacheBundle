Proxy Client Configuration
==========================

Usually, your application will interact with the caching proxy through the
:doc:`Cache Manager </reference/cache-manager>`. You need to configure a proxy client for
the cache manager to work. The proxy client is also available as a service
(``fos_http_cache.proxy_client``) that you can use directly.

Bundle Configuration
--------------------

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      proxy_client:
        varnish:
          servers: 123.123.123.1:6060, 123.123.123.2
          base_url: yourwebsite.com

.. glossary::

    ``servers``
        Comma-separated list of IP addresses or host names of your
        caching proxy servers. The port those servers will be contacted
        defaults to 6081, you can specify a different port with ``:<port>``.

    ``base_url``
        This must match the web host name clients are using when connecting
        to the caching proxy. Optionally can contain a base path to your
        application. Used for invalidation with paths.

.. warning::

    Double-check ``base_url``, for if it is mistyped, no content will be
    invalidated.

.. todo::

    **TODO: MOVE** When using ESI, you will want to purge individual fragments. To generate the
    corresponding ``_internal`` route, inject the ``http_kernel`` into your controller and
    use HttpKernel::generateInternalUri with the parameters as in the twig
    ``render`` tag.

Proxy Client Configuration
--------------------------

You need to configure your caching proxy (Varnish or Nginx) to work with this
bundle. Please refer to the :ref:`FOSHttpCache library’s documentation <foshttpcache:proxy-configuration>`
for more information.

Debug Header
~~~~~~~~~~~~

Enable the ``debug`` parameter to set a ``X-Cache-Debug`` header on each
response. You can then :ref:`configure your caching proxy <foshttpcache:varnish_debugging>`
to add debug information when that header is present:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      debug: true

The default value is ``%kernel.debug%``, triggering the header when you are in
dev mode but not in prod mode. You can change the header with the
``debug_header`` option:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      debug_header: Please-Send-Debug-Infos

