User Context
============

**Works with**:

* :ref:`Varnish <foshttpcache:varnish user context>`
* :doc:`symfony-http-cache`

If your application serves different content depending on the user's group
or context (guest, editor, admin), you can cache that content per user context.
Each user context (group) gets its own unique hash, which is then used to vary
content on. The event subscriber responds to hash requests and sets the Vary
header. This way, you can differentiate your content between user groups while
not having to store caches for each individual user.

.. note::

    Please read the :ref:`User Context <foshttpcache:user-context>`
    chapter in the FOSHttpCache documentation before continuing.

How It Works
------------

These five steps resemble the Overview in the FOSHttpCache documentation.

1. A :term:`foshttpcache:client` requests ``/foo``.
2. The :term:`foshttpcache:caching proxy` receives the request and holds it.
   It first sends a *hash request* to the *context hash route*.
3. The :term:`foshttpcache:application` receives the hash request. An event
   subscriber (``UserContextSubscriber``) aborts the request immediately after
   the Symfony2 firewall was applied. The application calculates the hash
   (``HashGenerator``) and then sends a response with the hash in a custom
   header (``X-User-Context-Hash`` by default).
4. The caching proxy receives the hash response, copies the hash header to the
   client’s original request for ``/foo`` and restarts that request.
5. If the response to ``/foo`` should differ per user context, the application
   sets a ``Vary: X-User-Context-Hash`` header. The appropriate user context
   dependent representation of ``/foo`` will then be returned to the client.

Configuration
-------------

First :ref:`configure your caching proxy <foshttpcache:user-context>`. Then
configure Symfony for handling hash lookups. The minimal steps are described
below, see the :doc:`reference </reference/configuration/user-context>` for
more details.

You need to configure a route for the context hash. It does not specify any
controller, as the request listener will abort the request right after the
firewall has been applied, but the route definition must exist. Use the same
path as you specified in the caching proxy and make sure that this path is
allowed for anonymous users and covered by your
`firewall configuration <http://symfony.com/doc/current/book/security.html>`_:

.. code-block:: yaml

    # app/config/routing.yml
    user_context_hash:
        path: /_fos_user_context_hash

If your access rules limit the whole site to logged in users, make sure to
handle the user context URL like the login page:

.. code-block:: yaml

    # app/config/security.yml
    access_control:
        - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/_fos_user_context_hash, roles: [IS_AUTHENTICATED_ANONYMOUSLY] }
        - { path: ^/, roles: ROLE_USER }

Finally, enable the subscriber with the default settings:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        user_context:
            enabled: true

.. note::

    When using the FOSRestBundle ``format_listener`` configuration on all paths
    of your site, the hash lookup will fail with "406 Not Acceptable - No
    matching accepted Response format could be determined". To avoid this
    problem, you can add a rule to the format listener configuration:

    ``- { path: '^/_fos_user_context_hash', stop: true }``

Generating Hashes
-----------------

When a context hash request is received, the ``HashGenerator`` is used to build
the context information. The generator does so by calling on one or more
*context providers*.

The bundle includes a simple ``role_provider`` that determines the hash from the
user’s roles. To enable it:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        user_context:
            role_provider: true

Alternatively, you can create a :ref:`custom context provider <custom-context-providers>`.

Caching Hash Responses
----------------------

To improve User Context Caching performance, you should cache the hash responses.
You can do so by configuring :ref:`hash_cache_ttl`.
