Proxy Client Configuration
==========================

Usually, your application will interact with the caching proxy through the
:doc:`Cache Manager </reference/cache-manager>`. You need to configure a proxy
client for the cache manager to work. The proxy client is also available as a
service (``fos_http_cache.proxy_client``) that you can use directly.

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

Configure for Varnish
---------------------

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      proxy_client:
        varnish:
          servers: 123.123.123.1:6060, 123.123.123.2
          base_url: yourwebsite.com

Configure for Nginx
-------------------

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      proxy_client:
        nginx:
          servers: 123.123.123.1:6060, 123.123.123.2
          base_url: yourwebsite.com
          purge_location: /purge

The options are the same as for Varnish with the addition of the
``purge_location`` used for the "different location" concept of Nginx.

.. tip::

    Although its not a common scenario, you can **use Nginx and Varnish in
    parallel**. If you need to cache and invalidate pages in both, you can
    configure both in this bundle. The CacheManager will however only use the
    default client. You can set the default with
    ``fos_http_client.proxy_client.default: nginx`` (resp. ``varnish``).

Custom Guzzle Client
--------------------

By default, the proxy client instantiates a Guzzle_ object to talk with the
caching proxy. If you need to customize the requests, for example to send a
basic authentication header, you can configure a service and specify that in
the ``guzzle_client`` option. A sample service definition for using basic
authentication looks like this:

.. code-block:: yaml

    acme.varnish.guzzle.client:
        class: Guzzle\Service\Client
        calls:
            - [setDefaultOption, [auth, [%varnish.username%, %varnish.password%, basic ]]]

Caching Proxy Configuration
---------------------------

You need to configure your caching proxy (Varnish or Nginx) to work with this
bundle. Please refer to the :ref:`FOSHttpCache library’s documentation <foshttpcache:proxy-configuration>`
for more information.

.. _Guzzle: http://guzzle3.readthedocs.org/
