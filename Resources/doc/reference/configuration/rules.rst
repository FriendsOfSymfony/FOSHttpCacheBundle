Rules
=====

Configure the bundle under the ``fos_http_cache`` key. The configuration
contains rules that determine caching and invalidation behaviour. There are
three types of rules:

1. Cache control rules
2. Tag rules
3. Invalidator rules

Each rule consists of:

* a matcher
* the rule effect

.. _match:

Matchers
~~~~~~~~

A matcher consists of criteria that determine whether the rule should be
applied. The rule is only applied when:

* the HTTP request matches *all* the matcher’s criteria
* the HTTP request is :term:`safe` (GET or HEAD)
* the HTTP response is considered :term:`cacheable` (override with
  :ref:`additional_cacheable_status` and :ref:`match_response`).

All matching criteria are regular expressions. For instance:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control
            rules:
                # only match login.example.com
                -
                    match:
                        host: ^login.example.com$
                        path: ^/$
                    # ...

host
""""

A regular expression to limit the caching rules to specific hosts, when you
serve more than one host from your Symfony application.

.. tip::

    To simplify caching of a site that at the same time has frontend
    editing, put the editing on a separate (sub-)domain. Then define a first
    rule matching that domain with ``host`` and set ``max-age: 0`` to make sure
    your caching proxy never caches the editing domain.

path
""""

For example, ``path: ^/`` will match every request. To only match the home
page, use ``path: ^/$``.

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
:term:`safe`.

You should not set both ``match_response`` and ``additional_cacheable_status``
inside the same rule.

