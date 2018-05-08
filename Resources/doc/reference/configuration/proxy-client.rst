proxy_client
============

The proxy client sends invalidation requests to your caching proxy. The
:doc:`Cache Manager </reference/cache-manager>` wraps the proxy client and is
the usual entry point for application interaction with the caching proxy.

You need to configure a client or define your own service for the cache manager
to work.

The proxy client is also directly available as a service. The default client
can be autowired with the ``FOS\HttpCache\ProxyClient\ProxyClient`` type
declaration or the service ``fos_http_cache.default_proxy_client``. Specific
clients, if configured, are available as ``fos_http_cache.proxy_client.varnish``
, ``fos_http_cache.proxy_client.nginx`` or ``fos_http_cache.proxy_client.symfony``).

If you need to adjust the proxy client, you can also configure the ``CacheManager``
with a :ref:`custom proxy client <custom_proxy_client>` that you defined as a
service. In that case, you do not need to configure anything in the
``proxy_client`` configuration section.

varnish
-------

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            varnish:
                tags_header: My-Cache-Tags
                tag_mode: ban
                header_length: 1234
                default_ban_headers:
                    Foo: Bar
                http:
                    servers:
                        - 123.123.123.1:6060
                        - 123.123.123.2
                    base_url: yourwebsite.com

``header_length``
"""""""""""""""""

**type**: ``integer`` **default**: ``7500``

Maximum header length when invalidating tags. If there are more tags to
invalidate than fit into the header, the invalidation request is split into
multiple requests.

``default_ban_headers``
"""""""""""""""""""""""

**type**: ``array``

Map of header name header value that have to be set on each ban request. This
list is merged with the built-in headers for bans.

``http.servers``
""""""""""""""""

**type**: ``array``

Comma-separated list of IP addresses or host names of your
caching proxy servers. The port those servers will be contacted
defaults to 80; you can specify a different port with ``:<port>``.

When using a multi-server setup, make sure to include **all** proxy servers in
this list. Invalidation must happen on all systems or you will end up with
inconsistent caches.

``http.base_url``
"""""""""""""""""

**type**: ``string``

The hostname (or base URL) where users access your web application. The base
URL may contain a path. If you access your web application on a port other than
80, include that port:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            varnish:
                http:
                    base_url: yourwebsite.com:8000

.. warning::

    Double-check ``base_url``, for if it is mistyped, no content will be
    invalidated.

``tag_mode``
""""""""""""

**type**: ``string`` **options**: ``ban``, ``purgekeys`` **default**: ``ban``

Select whether to invalidate tags using the `xkey vmod`_ or with BAN requests.

Xkey is an efficient way to invalidate Varnish cache entries based on
:doc:`tagging </features/tagging>`.

In mode ``purgekeys``, the bundle will default to using soft purges. If you do
not want to use soft purge (either because your varnish modules version is too
old to support it or because soft purging does not fit your scenario),
additionally set the ``tags_header`` option to ``xkey-purge`` instead of the
default ``xkey-softpurge``.

.. note::

    To use the purgekeys method, you need the `xkey vmod`_ enabled and VCL to
    handle xkey invalidation requests as explained in the
    :ref:`FOSHttpCache library docs on xkey support <foshttpcache:varnish_tagging>`.

``tags_header``
"""""""""""""""

**type**: ``string`` **default**: ``X-Cache-Tags`` if ``tag_mode`` is ``ban``, otherwise ``xkey-softpurge``

Header for sending tag invalidation requests to Varnish.

See the :ref:`FOSHttpCache library docs <foshttpcache:varnish configuration>`
on how to configure Varnish to handle tag invalidation requests.

nginx
-----

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            nginx:
                purge_location: /purge
                http:
                    servers:
                        - 123.123.123.1:6060
                        - 123.123.123.2
                    base_url: yourwebsite.com

For ``servers`` and ``base_url``, see above.

``purge_location``
""""""""""""""""""

**type**: ``string``

Separate location that purge requests will be sent to.

See the :ref:`FOSHttpCache library docs <foshttpcache:nginx configuration>`
on how to configure Nginx.

symfony
-------

You need to have a ``HttpCache`` capable of handling cache invalidation. Please
refer to the :ref:`FOSHttpCache documentation for Symfony <foshttpcache:symfony httpcache configuration>`.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            symfony:
                tags_header: My-Cache-Tags
                tags_method: TAGPURGE
                header_length: 1234
                purge_method: PURGE
                # for single server installations:
                # use_kernel_dispatcher: true
                http:
                    servers:
                        - 123.123.123.1:6060
                        - 123.123.123.2
                    base_url: yourwebsite.com

For ``servers``, ``base_url``, ``tags_header`` and ``header_length``, see above.

.. versionadded:: 2.3

    You can omit the whole ``http`` configuration and use ``use_kernel_dispatcher: true``
    instead. This will call the kernel directly instead of executing a real
    HTTP request. Note that your kernel and bootstrapping need to be adjusted
    to support this feature. The setup is explained in the
    :ref:`FOSHttpCache Symfony Proxy Client documentation section <foshttpcache:proxy client symfony httpcache kernel dispatcher>`.

``tags_method``
"""""""""""""""

**type**: ``string`` **default**: ``PURGETAGS``

HTTP method for sending tag invalidation requests to the Symfony HttpCache.
Make sure to configure the tags plugin for your HttpCache with the matching
header if you change this.

``purge_method``
""""""""""""""""

**type**: ``string`` **default**: ``PURGE``

HTTP method for sending purge requests to the Symfony HttpCache. Make sure to
configure the purge plugin for your HttpCache with the matching header if you
change this.

.. _configuration_noop_proxy_client:

noop
----

.. code-block:: yaml

    # app/config/config_test.yml
    fos_http_cache:
        proxy_client:
            default: noop
            noop: ~

This proxy client supports all invalidation methods, but implements doing
nothing (hence the name "no operation" client). This can be useful for testing.

default
-------

**type**: ``enum`` **options**: ``varnish``, ``nginx``, ``symfony``, ``noop``

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        proxy_client:
            default: varnish

If there is only one proxy client, it is automatically the default. Only
configure this if you configured more than one proxy client.

The default proxy client that will be used by the cache manager. You can
*configure Nginx, Varnish and Symfony proxy clients in parallel*. There is
however only one cache manager and it will only use the default client.

.. _custom HTTP client:

Custom HTTP Client
------------------

The proxy client uses a ``Http\Client\Utils\HttpMethodsClient`` wrapping a
``Http\Client\HttpClient`` instance. If you need to customize the requests, for
example to send a basic authentication header with each request, you can
configure a service for the ``HttpClient`` and specify that in the
``http_client`` option of any of the cache proxy clients.

Caching Proxy Configuration
---------------------------

You need to configure your caching proxy (Varnish or Nginx) to work with this
bundle. Please refer to the :ref:`FOSHttpCache library’s documentation <foshttpcache:proxy-configuration>`
for more information.

.. _xkey vmod: https://github.com/varnish/varnish-modules/blob/master/docs/vmod_xkey.rst
