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
, ``fos_http_cache.proxy_client.nginx``, ``fos_http_cache.proxy_client.symfony``
or ``fos_http_cache.proxy_client.cloudflare``).

If you need to adjust the proxy client, you can also configure the ``CacheManager``
with a :ref:`custom proxy client <custom_proxy_client>` that you defined as a
service. In that case, you do not need to configure anything in the
``proxy_client`` configuration section.

varnish
-------

.. code-block:: yaml

    # config/packages/fos_http_cache.yaml
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
                    # alternatively, if you configure the varnish servers in an environment variable:
                    # servers_from_jsonenv: '%env(json:VARNISH_SERVERS)%'
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

.. note::

    When using a variable amount of proxy servers that are defined via environment
    variable, use the ``http.servers_from_jsonenv`` option below.

``http.servers_from_jsonenv``
"""""""""""""""""""""""""""""

**type**: ``string``

JSON encoded servers array as string. The servers array has the same specs as ``http.servers``.

Use this option only when using a variable amount of proxy servers that shall be defined via
environment variable. Otherwise use the regular ``http.servers`` option.

Usage:
* ``fos_http_cache.yaml``: ``servers_from_jsonenv: '%env(json:VARNISH_SERVERS)%'``
* environment definition: ``VARNISH_SERVERS='["123.123.123.1:6060","123.123.123.2"]'``


``http.base_url``
"""""""""""""""""

**type**: ``string``

The hostname (or base URL) where users access your web application. The base
URL may contain a path. If you access your web application on a port other than
80, include that port:

.. code-block:: yaml

    # config/packages/fos_http_cache.yaml
    fos_http_cache:
        proxy_client:
            varnish:
                http:
                    base_url: yourwebsite.com:8000

.. warning::

    Double-check ``base_url``, for if it is mistyped, no content will be
    invalidated.

.. _config_varnish_tag_mode:

``tag_mode``
""""""""""""

**type**: ``string`` **options**: ``ban``, ``purgekeys`` **default**: ``ban``

Select whether to invalidate tags using the :ref:`xkey vmod <foshttpcache:varnish_tagging>`
or with BAN requests.

Xkey is an efficient way to invalidate Varnish cache entries based on
:doc:`tagging </features/tagging>`.

In mode ``purgekeys``, the bundle will default to using soft purges. If you do
not want to use soft purge (either because your varnish modules version is too
old to support it or because soft purging does not fit your scenario),
additionally set the ``tags_header`` option to ``xkey-purge`` instead of the
default ``xkey-softpurge``.

.. note::

    To use the purgekeys method, you need the xkey vmod enabled and VCL to
    handle xkey invalidation requests as explained in the
    :ref:`FOSHttpCache library docs on xkey support <foshttpcache:varnish_tagging>`.

    ``tags.response_header`` will automatically default to ``xkey`` when you
    set the mode to purgekeys.

``tags_header``
"""""""""""""""

**type**: ``string`` **default**: ``X-Cache-Tags`` if ``tag_mode`` is ``ban``, otherwise ``xkey-softpurge``

Header for sending tag invalidation requests to Varnish.

For use with ``tag_mode: purgekeys``, default VCL supports two options:
- ``xkey-softpurge``: "Soft purge" by tags, expires relevant cache and allows for grace handling.
- ``xkey-purge``: Purge by tags, removes relevant cache immediately.

See the :ref:`FOSHttpCache library docs <foshttpcache:varnish configuration>`
on how to configure Varnish to handle tag invalidation requests.

nginx
-----

.. code-block:: yaml

    # config/packages/fos_http_cache.yaml
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

    # config/packages/fos_http_cache.yaml
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
    :ref:`Symfony HttpCache chapter <symfony_http_cache_kernel_dispatcher>`.

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

cloudflare
-------

.. code-block:: yaml

    # config/packages/fos_http_cache.yaml
    fos_http_cache:
        proxy_client:
            cloudflare:
                zone_identifier: '<my-zone-identifier>'
                authentication_token: '<user-authentication-token>'
                http:
                    servers:
                        - 'https://api.cloudflare.com'

``authentication_token``
"""""""""""""""""""""""

**type**: ``string``

User API token for authentication against Cloudflare APIs, requires ``Zone.Cache`` Purge permissions.

``zone_identifier``
"""""""""""""""""

**type**: ``string``

Identifier for the Cloudflare zone you want to purge the cache for.

``http.servers``
""""""""""""""""

**type**: ``array`` **default**: ``['https://api.cloudflare.com']``

List of Cloudflare API endpoints to use for purging the cache. You can use this to specify a different
endpoint for testing purposes.

.. _configuration_noop_proxy_client:

noop
----

.. code-block:: yaml

    # config/packages/test/fos_http_cache.yaml
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

    # config/packages/fos_http_cache.yaml
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
