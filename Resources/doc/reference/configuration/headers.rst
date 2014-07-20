Caching Headers
===============

The configuration contains a number of *rules*. When a request matches the
parameters described in the ``match`` section, the headers as defined under
``headers`` will be set on its response.

The match patterns are applied in the order specified, where the first match
wins:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                # only match login.example.com
                -
                    match:
                        host: ^login.example.com$
                    headers:
                        cache_control: { public: false, max_age: 0, s_maxage: 0, last_modified: "-1 hour" }
                        vary: [Accept-Encoding, Accept-Language]

                # match all actions of a specific controller
                -
                    match:
                        attributes: { _controller: ^AcmeBundle:Default:.* }
                        additional_cacheable_status: [400]
                    headers:
                        cache_control: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }

                -
                    match:
                        path: ^/$
                    headers:
                        cache_control: { public: true, max_age: 64000, s_maxage: 64000, last_modified: "-1 hour" }
                        vary: [Accept-Encoding, Accept-Language]

                # match everything to set defaults
                -
                    match:
                      path: ^/
                    headers:
                        cache_control: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }

rules
-----

.. sidebar:: Merging headers

    If the response already has certain cache directives set, they are not
    overwritten. The configuration can thus specify defaults that may be
    changed by controllers or services that handle the response, or ``@Cache``
    annotations.

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

The configuration contains a number of *rules*. When a request matches the
parameters described in the ``match`` section, the headers as defined under
``headers`` will be set on the response, if they are not already set.

The match patterns are applied in the order specified, where the first match
wins.

.. _match:

match
-----

The headers are only set if all of these are true:

* the request matches *all* criteria defined under ``match``
* the request is :term:`safe` (GET or HEAD)
* the response is considered :term:`cacheable` (override with
  :ref:`additional_cacheable_status` and :ref:`match_response`).

All matching criteria are regular expressions.

path
~~~~

For example, ``path: ^/`` will match every request. To only match the home
page, use ``path: ^/$``.

host
~~~~

A regular expression to limit the caching rules to specific hosts, when you
serve more than one host from your Symfony application.

.. tip::

    To simplify caching of a site that at the same time has frontend
    editing, put the editing on a separate (sub-)domain. Then define a first
    rule matching that domain with ``host`` and set ``max-age: 0`` to make sure
    your caching proxy never caches the editing domain.

methods
~~~~~~~

Can be used to limit caching rules to specific HTTP methods like GET requests.

Note that cache headers are not applied to methods not considered *safe*, not
even when the methods are listed in this configuration.

ips
~~~

An array that can be used to limit the rules to a specified set of request
client IP addresses.

.. note::

    If you use a caching proxy and want specific IPs to see different headers,
    you need to forward the client IP to the backend. Otherwise, the backend
    only sees the caching proxy IP. See `Trusting Proxies`_ in the Symfony
    documentation.

attributes
~~~~~~~~~~

An array to filter on route attributes. the most common use case would be
``_controller`` when you need caching rules applied to a controller. Note that
this is the controller name used in the route, so it depends on your route
configuration whether you need ``Bundle:Name:action`` or
``service.id:methodName`` (if you defined your `controllers as services`_).

Note that even for the request attributes, your criteria are interpreted as
regular expressions.

.. _additional_cacheable_status:

additional_cacheable_status
~~~~~~~~~~~~~~~~~~~~~~~~~~~

A list of additional HTTP status codes of the response for which to also apply
the rule.

.. _match_response:

match_response
~~~~~~~~~~~~~~

.. note::

    ``match_response`` :ref:`requires the ExpressionLanguage component <requirements>`.

An ExpressionLanguage expression to decide whether the response should have
the headers applied. If not set, headers are applied if the request is
:term:`safe`. The expression can access the ``Response`` object with the
``response`` variable. For example, to handle all failed requests, you can do:

.. code-block:: yaml

    -
        match:
            match_response: response.getStatusCode() >= 400
        # ...

You can not set both ``match_response`` and ``additional_cacheable_status``
inside the same rule.

headers
-------

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

cache_control
~~~~~~~~~~~~~

The map under ``cache_control`` is set in a call to ``Response::setCache()``.
The names are specified with underscores in yml, but translated to ``-`` for
the ``Cache-Control`` header.

You can use the standard cache control directives:

* ``max_age`` time in seconds;
* ``s_maxage`` time in seconds for proxy caches (also public caches);
* ``private`` true or false;
* ``public`` true or false;
* ``no_cache`` true or false (use exclusively to support HTTP 1.0);

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

    The cache-control headers are described in detail in :rfc:`2616#section-14.9`.

Extra cache control directives
""""""""""""""""""""""""""""""

You can also set headers that Symfony considers non-standard, some coming from
RFCs extending HTTP/1.1. The following options are supported:

* ``must_revalidate`` (:rfc:`2616#section-14.9`)
* ``proxy_revalidate`` (:rfc:`2616#section-14.9`)
* ``no_transform`` (:rfc:`2616#section-14.9`)
* ``stale_if_error``: seconds (:rfc:`5861`)
* ``stale_while_revalidate``: seconds (:rfc:`5861`)

The *stale* directives need a parameter specifying the time in seconds how long
a  cache is allowed to continue serving stale content if needed. The other
directives are flags that are included when set to true.

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

last_modified
~~~~~~~~~~~~~

The input to the ``last_modified`` is used for the ``Last-Modified`` header.
This value must be a valid input to ``DateTime``.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        last_modified: "-1 hour"

.. hint::

    Setting an arbitrary last modified time allows clients to send
    ``If-Modified-Since`` requests. Varnish can handle these to serve data
    from the cache if it was not invalidated since the client requested it.

vary
~~~~

You can set the `vary` option to an array that defines the contents of the
`Vary` header when matching the request. This adds to existing Vary headers,
keeping previously set Vary options.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        vary: My-Custom-Header

reverse_proxy_ttl for X-Reverse-Proxy-TTL for Custom Reverse Proxy Time-Outs
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, reverse proxies use the ``s-maxage`` of your ``Cache-Control`` header
to know how long it should cache a page. But by default, the s-maxage is also
sent to the client. Any caches on the internet, for example at an internet
provider or in the office of a surfer, might look at ``s-maxage`` and
cache the page if it is ``public``. This can be a problem, notably when you do
:doc:`explicit cache invalidation </reference/cache-manager>`. You might want your reverse
proxy to keep a page in cache for a long time, but outside caches should not
keep the page for a long duration.

One option could be to set a high ``s-maxage`` for the proxy and simply rewrite
the response to remove or reduce the ``s-maxage``. This is not a good solution
however, as you start to duplicate your caching rule definitions.

This bundle helps you to build a better solution: You can specify the option
``reverse_proxy_ttl`` in the headers section to get a special header that you can
then use on the reverse proxy:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            rules:
                -
                    headers:
                        reverse_proxy_ttl: 3600
                        cache_control: { public: true, s_maxage: 60 }

This example adds the header ``X-Reverse-Proxy-TTL: 3600`` to your responses.
Varnish by default knows nothing about this header. To make this solution work,
you need to extend your varnish ``vcl_fetch`` configuration:

.. code-block:: c

    sub vcl_fetch {
        if (beresp.http.X-Reverse-Proxy-TTL) {
            C{
                char *ttl;
                ttl = VRT_GetHdr(sp, HDR_BERESP, "\024X-Reverse-Proxy-TTL:");
                VRT_l_beresp_ttl(sp, atoi(ttl));
            }C
            unset beresp.http.X-Reverse-Proxy-TTL;
        }
    }

Note that there is a ``beresp.ttl`` field in VCL but unfortunately it can only
be set to absolute values and not dynamically. Thus we have to revert to a C
code fragment.

.. _Trusting Proxies: http://symfony.com/doc/current/components/http_foundation/trusting_proxies.html
.. _controllers as services: http://symfony.com/doc/current/cookbook/controller/service.html
