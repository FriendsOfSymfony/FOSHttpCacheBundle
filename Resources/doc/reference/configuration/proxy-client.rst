proxy_client
============

The proxy client sends invalidation requests to your caching proxy. It must be
configured for the :doc:`Cache Manager </reference/cache-manager>` to work,
which wraps the proxy client and is the usual entry point for application
interaction with the caching proxy. The proxy client is also available as a
service (``fos_http_cache.proxy_client``) that you can use directly.

varnish
-------

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            varnish:
                servers: 123.123.123.1:6060, 123.123.123.2
                base_url: yourwebsite.com

servers
"""""""

**type**: ``array``

Comma-separated list of IP addresses or host names of your
caching proxy servers. The port those servers will be contacted
defaults to 80; you can specify a different port with ``:<port>``.

base_url
""""""""

**type**: ``string``

The hostname (or base URL) where users access your web application. The base
URL may contain a path. If you access your web application on a port other than
80, include that port:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            varnish:
                base_url: yourwebsite.com:8000

.. warning::

    Double-check ``base_url``, for if it is mistyped, no content will be
    invalidated.

See the :ref:`FOSHttpCache library docs <foshttpcache:varnish configuration>`
on how to configure Varnish.

nginx
-----

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            nginx:
                servers: 123.123.123.1:6060, 123.123.123.2
                base_url: yourwebsite.com
                purge_location: /purge

For ``servers`` and ``base_url``, see above.

purge_location
""""""""""""""

**type**: ``string``

Separate location that purge requests will be sent to.

See the :ref:`FOSHttpCache library docs <foshttpcache:nginx configuration>`
on how to configure Nginx.

default
-------

**type**: ``enum`` **options**: ``varnish``, ``nginx``

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            default: varnish

The default proxy client that will be used by the cache manager.
You can *use Nginx and Varnish in parallel*. If you need to cache and
invalidate pages in both, you can configure both in this bundle.
The cache manager however will only use the default client.

Custom Guzzle Client
--------------------

By default, the proxy client instantiates a `Guzzle client`_ to talk with the
caching proxy. If you need to customize the requests, for example to send a
basic authentication header, you can configure a service and specify that in
the ``guzzle_client`` option. A sample service definition for using basic
authentication looks like this:

.. code-block:: yaml

    # app/config/config.yml
    acme.varnish.guzzle.client:
        class: Guzzle\Service\Client
        calls:
            - [setDefaultOption, [auth, [%varnish.username%, %varnish.password%, basic ]]]

Caching Proxy Configuration
---------------------------

You need to configure your caching proxy (Varnish or Nginx) to work with this
bundle. Please refer to the :ref:`FOSHttpCache library’s documentation <foshttpcache:proxy-configuration>`
for more information.

.. _Guzzle client: http://guzzle3.readthedocs.org/
