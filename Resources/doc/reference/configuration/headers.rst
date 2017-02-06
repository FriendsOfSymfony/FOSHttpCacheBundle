cache_control
=============

The configuration contains a number of *rules*. When a request matches the
parameters described in the ``match`` section, the headers as defined under
``headers`` will be set on the response, if they are not already set. Rules are
checked in the order specified, where the first match wins.

A global setting and a per rule ``overwrite`` option allow to overwrite the
cache headers even if they are already set:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            defaults:
                overwrite: false
            rules:
                # only match login.example.com
                -
                    match:
                        host: ^login.example.com$
                    headers:
                        overwrite: true
                        cache_control:
                            public: false
                            max_age: 0
                            s_maxage: 0
                        etag: true
                        vary: [Accept-Encoding, Accept-Language]

                # match all actions of a specific controller
                -
                    match:
                        attributes: { _controller: ^Acme\\TestBundle\\Controller\\DefaultController::.* }
                        additional_cacheable_status: [400]
                    headers:
                        cache_control:
                            public: true
                            max_age: 15
                            s_maxage: 30
                        last_modified: "-1 hour"

                -
                    match:
                        path: ^/$
                    headers:
                        cache_control:
                            public: true
                            max_age: 64000
                            s_maxage: 64000
                        etag: true
                        vary: [Accept-Encoding, Accept-Language]

                # match everything to set defaults
                -
                    match:
                        path: ^/
                    headers:
                        cache_control:
                            public: true
                            max_age: 15
                            s_maxage: 30
                        etag: true

``rules``
---------

**type**: ``array``

A set of cache control rules consisting of *match* criteria and *header* instructions.

.. include:: /includes/match.rst

headers
^^^^^^^

**type**: ``array``

.. sidebar:: YAML alias for same headers for different matches

    If you have many rules that should end up with the same headers, you
    can use YAML "aliases" *within the same configuration file* to avoid
    redundant configuration. The ``&alias`` notation creates an alias, the
    ``<< : *alias`` notation inserts the aliased configuration. You can then
    still overwrite parts of the aliased configuration. An example would be:

    .. code-block:: yaml

        rules:
            -
                match:
                    path: ^/products.*
                headers: &public
                    cache_control:
                        public: true
                        max_age: 600
                        s_maxage: 300
                    reverse_proxy_ttl: 3600
            -
                match:
                    path: ^/brands.*
                headers:
                    << : *public
                    cache_control:
                        max_age: 1800

In the ``headers`` section, you define what headers to set on the response if
the request was matched.

Headers are **merged**. If the response already has certain cache directives
set, they are not overwritten. The configuration can thus specify defaults
that may be changed by controllers or services that handle the response, or
``@Cache`` annotations.

The listener that applies the rules is triggered at priority 10, which
makes it handle before the ``@Cache`` annotations from the
SensioFrameworkExtraBundle are evaluated. Those annotations unconditionally
overwrite cache directives.

The only exception is responses that *only* have the ``no-cache``
directive. This is the default value for the cache control and there is no
way to determine if it was manually set. If the full header is only
``no-cache``, the whole cache control is overwritten.

You can prevent the cache control on specific requests by injecting the
service ``fos_http_cache.event_listener.cache_control`` and calling
``setSkip()`` on it. If this method is called, no cache rules are applied.

``cache_control``
"""""""""""""""""

**type**: ``array``

The map under ``cache_control`` is set in a call to ``Response::setCache()``.
The names are specified with underscores in yaml, but translated to ``-`` for
the ``Cache-Control`` header.

You can use the standard cache control directives:

* ``max_age`` time in seconds;
* ``s_maxage`` time in seconds for proxy caches (also public caches);
* ``private`` true or false;
* ``public`` true or false;
* ``no_cache`` true or false (use exclusively to support HTTP 1.0);
* ``no_store``: true or false.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        cache_control:
                            public: true
                            max_age: 64000
                            s_maxage: 64000

If you use ``no_cache``, you should *not set any other options*. This will make
Symfony properly handle HTTP 1.0, setting the ``Pragma: no-cache`` and
``Expires: -1`` headers. If you add other cache_control options, Symfony will
not do this handling. Note that Varnish 3 does not respect ``no-cache`` by
default. If you want it respected, add your own logic to ``vcl_fetch``.

.. note::

    The cache-control headers are described in detail in :rfc:`2616#section-14.9`
    and further clarified in :rfc:`7234#section-5.2`.

Extra Cache Control Directives
""""""""""""""""""""""""""""""

You can also set headers that Symfony considers non-standard, some coming from
RFCs extending :rfc:`2616` HTTP/1.1. The following options are supported:

* ``must_revalidate`` (:rfc:`7234#section-5.2.2.1`)
* ``proxy_revalidate`` (:rfc:`7234#section-5.2.2.7`)
* ``no_transform`` (:rfc:`7234#section-5.2.2.4`)
* ``stale_if_error``: seconds (:rfc:`5861#section-4`)
* ``stale_while_revalidate``: seconds (:rfc:`5861#section-3`)

The *stale* directives need a parameter specifying the time in seconds how long
a  cache is allowed to continue serving stale content if needed. The other
directives are flags that are included when set to true:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    path: ^/$
                    headers:
                        cache_control:
                            stale_while_revalidate: 9000
                            stale_if_error: 3000
                            must_revalidate: true
                            proxy_revalidate: true
                            no_transform: true

``etag``
""""""""

**type**: ``boolean``

This enables a simplistic ETag calculated as md5 hash of the response body:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        etag: true

.. tip::

    This simplistic ETag handler will not help you to prevent unnecessary work
    on your web server, but allows a caching proxy to use the ETag cache
    validation method to preserve bandwidth. The presence of an ETag tells
    clients that they can send a ``If-None-Match`` header with the ETag their
    current version of the content has. If the caching proxy still has the same
    ETag, it responds with a "304 Not Modified" status.

    You can get additional performance if you write your own ETag handler that
    can read an ETag from your content and decide very early in the request
    whether the ETag changed or not. It can then terminate the request early
    with an empty "304 Not Modified" response. This avoids rendering the whole
    page. If the page depends on permissions, make sure to make the ETag differ
    based on those permissions (e.g. by appending the :doc:`user context hash </features/user-context>`).

``last_modified``
"""""""""""""""""

**type**: ``string``

The input to the ``last_modified`` is used for the ``Last-Modified`` header.
This value must be a valid input to ``DateTime``:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        last_modified: "-1 hour"

.. note::

    Setting an arbitrary last modified time allows clients to send
    ``If-Modified-Since`` requests. Varnish can handle these to serve data
    from the cache if it was not invalidated since the client requested it.

    Note that the default system will generate an arbitrary last modified date.
    You can get additional performance if you write your own last modified
    handler that can compare this date with information about the content of
    your page and decide early in the request whether anything changed. It can
    then terminate the request early with an empty "304 Not Modified" response.
    Using content meta data increases the probability for a 304 response and
    avoids rendering the whole page.

    See also :rfc:`7232#section-2.2.1` for further consideration on how to
    generate the last modified date.

.. note::

    You may configure both ETag and last modified on the same response. See
    :rfc:`7232#section-2.4` for more details.

``vary``
""""""""

**type**: ``string``

You can set the `vary` option to an array that defines the contents of the
`Vary` header when matching the request. This adds to existing Vary headers,
keeping previously set Vary options:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        vary: My-Custom-Header

``reverse_proxy_ttl``
"""""""""""""""""""""

**type**: ``integer``

Set a X-Reverse-Proxy-TTL header for reverse proxy time-outs not driven by
``s-maxage``. This keeps your ``s-maxage`` free for use with reverse proxies
not under your control.

.. warning::

    This is a custom header. You need to set up your caching proxy to respect
    this header. See the FOSHttpCache documentation
    :ref:`for Varnish <foshttpcache:varnish configuration>` or
    :ref:`for the Symfony HttpCache <foshttpcache:symfony httpcache configuration>`.

To use the custom TTL, specify the option ``reverse_proxy_ttl`` in the headers
section:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        reverse_proxy_ttl: 3600
                        cache_control:
                            public: true
                            s_maxage: 60

This example adds the header ``X-Reverse-Proxy-TTL: 3600`` to your responses.
