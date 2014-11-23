user_context
============

This chapter describes how to configure user context caching. See
the :doc:`User Context Feature chapter </features/user-context>` for
an introduction to the subject.

Configuration
-------------

Caching Proxy Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Varnish
"""""""

Set up Varnish caching proxy as explained in the
:ref:`user context documentation <foshttpcache:user-context>`.

Symfony reverse proxy
"""""""""""""""""""""

Set up Symfony reverse proxy as explained in the :doc:`Symfony HttpCache dedicated documentation page </features/symfony-http-cache>`.

Context Hash Route
~~~~~~~~~~~~~~~~~~

Then add the route you specified in the hash lookup request to the Symfony2
routing configuration, so that the user context event subscriber can get
triggered:

.. code-block:: yaml

    # app/config/routing.yml
    user_context_hash:
        /user-context-hash

.. important::

    If you are using `Symfony2 security <http://symfony.com/doc/current/book/security.html>`_
    for the hash generation, make sure that this route is inside the firewall
    for which you are doing the cache groups.

.. note::

    This route is never actually used, as the context event subscriber will act
    before a controller would be called. But the user context is handled only
    after security happened. Security in turn only happens after the routing.
    If the routing does not find a route, the request is aborted with a ‘not
    found’ error and the listener is never triggered.

    The event subscriber has priority ``7`` which makes it act right after the
    security listener which has priority ``8``. The reason to use a listener
    here rather than a controller is that many expensive operations happen
    later in the handling of the request. Having this listener avoids those.

enabled
~~~~~~~

**type**: ``enum`` **default**: ``auto`` **options**: ``true``, ``false``, ``auto``

Set to ``true`` to explicitly enable the subscriber. The subscriber is
automatically enabled if you configure any of the ``user_context`` options.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      user_context:
        enabled: true

hash_header
~~~~~~~~~~~

**type**: ``string`` **default**: ``X-User-Context-Hash``

The name of the HTTP header that the event subscriber will store the
context hash in when responding to hash requests. Every other response will
vary on this header.

match
~~~~~

accept
""""""

**type**: ``string`` **default**: ``application/vnd.fos.user-context-hash``

HTTP Accept header that hash requests use to get the context hash. This must
correspond to your caching proxy configuration.

method
""""""

**type**: ``string``

HTTP method used by context hash requests, most probably either ``GET`` or
``HEAD``. This must correspond to your caching proxy configuration.

matcher_service
"""""""""""""""

**type**: ``string`` **default**: ``fos_http_cache.user_context.request_matcher``

Id of a service that determines whether a request is a context hash request.
The service must implement ``Symfony\Component\HttpFoundation\RequestMatcherInterface``.
If set, ``accept`` and ``method`` will be ignored.

hash_cache_ttl
~~~~~~~~~~~~~~

**type**: ``integer`` **default**: `0`

Time in seconds that context hash responses will be cached. Value `0` means
caching is disabled. For performance reasons, it makes sense to cache the hash
generation response; after all, each content request may trigger a hash
request. However, when you decide to cache hash responses, you must invalidate
them when the user context changes, particularly when the user logs in or out.
This bundle provides a logout handler that takes care of this for you.

logout_handler
~~~~~~~~~~~~~~

The logout handler will invalidate any cached user hashes when the user logs
out.

For the handler to work:

* your caching proxy should be :ref:`configured for BANs <foshttpcache:proxy-configuration>`
* Symfony’s default behaviour of regenerating the session id when users log in
  and out must be enabled (``invalidate_session``).

Add the handler to your firewall configuration:

.. code-block:: yaml

    # app/config/security.yml
    security:
        firewalls:
            secured_area:
                logout:
                    invalidate_session: true
                    handlers:
                        - fos_http_cache.user_context.logout_handler

enabled
"""""""

**type**: ``enum`` **default**: ``auto`` **options**: ``true``, ``false``, ``auto``

Defauts to ``auto``, which enables the logout handler service if a
:doc:`proxy client </reference/configuration/proxy-client>` is configured.
Set to ``true`` to explicitly enable the logout handler. This will throw an
exception if no proxy client is configured. 

user_identifier_headers
~~~~~~~~~~~~~~~~~~~~~~~

**type**: ``array`` **default**: ``['Cookie', 'Authorization']``

Determines which HTTP request headers the context hash responses will vary on.

If the hash only depends on the ``Authorization`` header and should be cached
for 15 minutes, configure:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      user_context:
        user_identifier_headers:
          - Authorization
        hash_cache_ttl: 900

role_provider
~~~~~~~~~~~~~

**type**: ``boolean`` **default**: ``false``

One of the most common scenarios is to differentiate the content based on the
roles of the user. Set ``role_provider`` to ``true`` to determine the hash from
the user’s roles. If there is a security context that can provide the roles,
all roles are added to the hash:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache
      user_context:
        role_provider: true

.. _custom-context-providers:

Custom Context Providers
------------------------

Custom providers need to:

* implement ``FOS\HttpCache\UserContext\ContextProviderInterface``
* be tagged with ``fos_http_cache.user_context_provider``.

The ``updateUserContext(UserContext $context)`` method is called when the hash
is generated.

.. code-block:: yaml

    acme.demo_bundle.my_service:
      class: "%acme.demo_bundle.my_service.class%"
      tags:
        - { name: fos_http_cache.user_context_provider }

.. code-block:: xml

    <service id="acme.demo_bundle.my_service" class="%acme.demo_bundle.my_service.class%">
        <tag name="fos_http_cache.user_context_provider" />
    </service>
