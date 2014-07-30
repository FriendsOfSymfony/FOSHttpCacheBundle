User Context
============

**Prerequisites**: :ref:`configure caching proxy for user context <foshttpcache:varnish user context>` (Varnish only).

If your application serves different content depending on the user’s group
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

First configure your caching proxy and application as described in the
`:doc:/reference/configuration/user-context` chapter.

Then you can enable the subscriber with the default settings:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        user_context:
            enabled: true

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
