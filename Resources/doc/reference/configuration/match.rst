match
=====

The :doc:`cache <headers>`, :doc:`invalidation <invalidation>` and
:doc:`tag rule <tags>` configurations all use ``match`` sections
to limit the configuration to specific requests and responses.

Each ``match`` section contains one or more match criteria for requests.
All criteria are regular expressions. For instance:

.. code-block:: yaml

    match:
        host: ^login.example.com$
        path: ^/$

host
----

**type**: ``string``

A regular expression to limit the caching rules to specific hosts, when you
serve more than one host from your Symfony application.

.. tip::

    To simplify caching of a site that at the same time has frontend
    editing, put the editing on a separate (sub-)domain. Then define a first
    rule matching that domain with ``host`` and set ``max-age: 0`` to make sure
    your caching proxy never caches the editing domain.

path
----

**type**: ``string``

For example, ``path: ^/`` will match every request. To only match the home
page, use ``path: ^/$``.

methods
-------

**type**: ``array``

Can be used to limit caching rules to specific HTTP methods like GET requests.
Note that the rule effect is not applied to :term:`unsafe <safe>` methods, not
even when you set the methods here:

.. code-block:: yaml

    match:
        methods: [PUT, DELETE]

ips
---

**type**: ``array``

An array that can be used to limit the rules to a specified set of request
client IP addresses.

.. note::

    If you use a caching proxy and want specific IPs to see different headers,
    you need to forward the client IP to the backend. Otherwise, the backend
    only sees the caching proxy IP. See `Trusting Proxies`_ in the Symfony
    documentation.

attributes
----------

**type**: ``array``

An array of request attributes to match against. Each attribute is interpreted
as a regular expression.

_controller
^^^^^^^^^^^

**type**: ``string``

Controller name regular expression. Note that this is the controller name used
in the route, so it depends on your route configuration whether you need
``Acme\\TestBundle\\Controller\\NameController::hello`` or ``acme_test.controller.name:helloAction``
for `controllers as services`_.

.. warning::

    Symfony always expands the short notation in route definitions. Even if you
    define your route as ``AcmeTestBundle:Name:hello`` you still need to use
    the long form here. If you use a service however, the compiled route still
    uses the service name and you need to match on that. If you mixed both, you
    can do a regular expression like ``^(Acme\\TestBundle|acme_test.controller)``.

_route
^^^^^^

**type**: ``string``

Route name regular expression. To match a single route:

.. code-block:: yaml

    match:
        attributes:
            route: ^articles_index$

To match multiple routes:

.. code-block:: yaml

    match:
        attributes:
            route: ^articles.*|news$

Note that even for the request attributes, your criteria are interpreted as
regular expressions.

.. code-block:: yaml

    match:
        attributes: { _controller: ^AcmeBundle:Default:.* }

.. _additional_cacheable_status:

additional_cacheable_status
---------------------------

**type**: ``array``

A list of additional HTTP status codes of the response for which to also apply
the rule.

.. code-block:: yaml

    match:
        additional_cacheable_status: [400, 403]

.. _match_response:

match_response
--------------

**type**: ``string``

.. note::

    ``match_response`` :ref:`requires the ExpressionLanguage component <requirements>`.

An ExpressionLanguage expression to decide whether the response should have
the effect applied. If not set, headers are applied if the request is
:term:`safe`. The expression can access the ``Response`` object with the
``response`` variable. For example, to handle all failed requests, you can do:

.. code-block:: yaml

    -
        match:
            match_response: response.getStatusCode() >= 400
        # ...

You cannot set both ``match_response`` and ``additional_cacheable_status``
inside the same rule.

.. _Trusting Proxies: http://symfony.com/doc/current/components/http_foundation/trusting_proxies.html
.. _controllers as services: http://symfony.com/doc/current/cookbook/controller/service.html
