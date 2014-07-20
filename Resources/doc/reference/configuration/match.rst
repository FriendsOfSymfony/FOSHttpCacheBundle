match
=====

**type**: ``array``

Defines the matching part of a :doc:`cache <headers>`, :doc:`invalidation <invalidation>`
or :doc:`tag rule <tags>`. It contains one or more match criteria for
requests. All criteria are regular expressions. They are checked in the order
specified, where the first match wins.

All matching criteria are regular expressions. For instance:

.. code-block:: yaml

    match:
        host: ^login.example.com$
        path: ^/$

host
""""

**type**: ``string``

A regular expression to limit the caching rules to specific hosts, when you
serve more than one host from your Symfony application.

.. tip::

    To simplify caching of a site that at the same time has frontend
    editing, put the editing on a separate (sub-)domain. Then define a first
    rule matching that domain with ``host`` and set ``max-age: 0`` to make sure
    your caching proxy never caches the editing domain.

path
""""

**type**: ``string``

For example, ``path: ^/`` will match every request. To only match the home
page, use ``path: ^/$``.

methods
"""""""

**type**: ``array``

Can be used to limit caching rules to specific HTTP methods like GET requests.
Note that the rule effect is not applied to :term:`unsafe <safe>` methods, not
even when you set the methods here:

.. code-block:: yaml

    match:
        methods: [PUT, DELETE]

ips
"""

**type**: ``array``

An array that can be used to limit the rules to a specified set of request
client IP addresses.

.. note::

    If you use a caching proxy and want specific IPs to see different headers,
    you need to forward the client IP to the backend. Otherwise, the backend
    only sees the caching proxy IP. See `Trusting Proxies`_ in the Symfony
    documentation.

attributes
""""""""""

**type**: ``array``

An array to filter on route attributes. the most common use case would be
``_controller`` when you need caching rules applied to a controller. Note that
this is the controller name used in the route, so it depends on your route
configuration whether you need ``Bundle:Name:action`` or
``service.id:methodName`` (if you defined your `controllers as services`_).

Note that even for the request attributes, your criteria are interpreted as
regular expressions.

.. code-block:: yaml

    match:
        attributes: { _controller: ^AcmeBundle:Default:.* }

.. _additional_cacheable_status:

additional_cacheable_status
"""""""""""""""""""""""""""

**type**: ``array``

A list of additional HTTP status codes of the response for which to also apply
the rule.

.. code-block:: yaml

    match:
        additional_cacheable_status: [400, 403]

.. _match_response:

match_response
""""""""""""""

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
