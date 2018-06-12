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

Set up Symfony reverse proxy as explained in the :doc:`Symfony HttpCache documentation </features/symfony-http-cache>`.

Context Hash Route
~~~~~~~~~~~~~~~~~~

Then add the route you specified in the hash lookup request to the Symfony
routing configuration, so that the user context event subscriber can get
triggered:

.. code-block:: yaml

    # app/config/routing.yml
    user_context_hash:
        path: /_fos_user_context_hash

.. important::

    If you are using `Symfony security <http://symfony.com/doc/current/book/security.html>`_
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

``enabled``
~~~~~~~~~~~

**type**: ``enum`` **default**: ``auto`` **options**: ``true``, ``false``, ``auto``

Set to ``true`` to explicitly enable the subscriber. The subscriber is
automatically enabled if you configure any of the ``user_context`` options.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        user_context:
            enabled: true

``hash_header``
~~~~~~~~~~~~~~~

**type**: ``string`` **default**: ``X-User-Context-Hash``

The name of the HTTP header that the event subscriber will store the
context hash in when responding to hash requests. Every other response will
vary on this header.

``match``
~~~~~~~~~

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

.. _hash_cache_ttl:

``hash_cache_ttl``
~~~~~~~~~~~~~~~~~~

**type**: ``integer`` **default**: ``0``

Time in seconds that context hash responses will be cached. Value ``0`` means
caching is disabled. For performance reasons, it makes sense to cache the hash
generation response; after all, each content request may trigger a hash
request. However, when you decide to cache hash responses, you must invalidate
them when the user context changes, particularly when the user logs in or out.
This bundle provides a logout handler that takes care of this for you.

``always_vary_on_context_hash``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**type**: ``boolean`` **default**: ``true``

This bundle automatically adds the Vary header for the user context hash, so
you don't need to do this yourself or :doc:`configure it as header <headers>`.
If the hash header is missing from a request for some reason, the response is
set to vary on the user identifier headers to avoid problems.

If not all your pages depend on the hash, you can set
``always_vary_on_context_hash`` to  ``false`` and handle the Vary yourself.
When doing that, you have to be careful to set the Vary header whenever needed,
or you will end up with mixed up caches.

``logout_handler``
~~~~~~~~~~~~~~~~~~

The logout handler will invalidate any cached user hashes when the user logs
out.

For the handler to work:

* your caching proxy should be :ref:`configured for BANs <foshttpcache:proxy-configuration>`
* Symfony’s default behavior of regenerating the session id when users log in
  and out must be enabled (``invalidate_session``).

.. warning::
    The cache invalidation on logout only works correctly with FOSHttpCacheBundle 2.2 and later.
    It was broken in older versions of the bundle.

.. tip::
    The logout handler is active on all firewalls.  If your application has
    multiple firewalls with different user context, you need to create your own
    custom invalidation handler. Be aware that Symfony's ``LogoutSuccessHandler``
    places the ``SessionLogoutHandler`` that invalidates the old session
    *before* any configured logout handlers.

enabled
"""""""

**type**: ``enum`` **default**: ``auto`` **options**: ``true``, ``false``, ``auto``

Defaults to ``auto``, which enables the logout handler service if a
:doc:`proxy client </reference/configuration/proxy-client>` is configured.
Set to ``true`` to explicitly enable the logout handler. This will throw an
exception if no proxy client is configured.

``user_identifier_headers``
~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

The ``Cookie`` header is automatically added to this list unless ``session_name_prefix``
is set to ``false``.

``session_name_prefix``
~~~~~~~~~~~~~~~~~~~~~~~

**type**: ``string`` **default**: ``PHPSESSID``

Defines which cookie is the session cookie. Normal cookies will be ignored in
user context and only the session cookie is taken into account. It is
recommended that you clean up the cookie header to avoid any other cookies in
your requests.

If you set this configuration to ``false``, cookies are completely ignored. If
you add the ``Cookie`` header to ``user_identifier_headers``, any cookie will
make the request not anonymous.

``role_provider``
~~~~~~~~~~~~~~~~~

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

* implement the ``FOS\HttpCache\UserContext\ContextProvider`` interface
* be tagged with ``fos_http_cache.user_context_provider``.

If you need context providers to run in a specific order, you can specify the
optional ``priority`` parameter for the tag. The higher the priority, the
earlier a context provider is executed. The build-in provider has a priority
of 0.

The ``updateUserContext(UserContext $context)`` method of the context provider
is called when the hash is generated.

.. code-block:: yaml

    acme.demo_bundle.my_service:
        class: "%acme.demo_bundle.my_service.class%"
        tags:
            - { name: fos_http_cache.user_context_provider, priority: 10 }

.. code-block:: xml

    <service id="acme.demo_bundle.my_service" class="%acme.demo_bundle.my_service.class%">
        <tag name="fos_http_cache.user_context_provider" priority="10" />
    </service>

.. code-block:: php

    $container
        ->register('acme.demo_bundle.my_service', '%acme.demo_bundle.my_service.class%')
        ->addTag('fos_http_cache.user_context_provider', array('priority' => 10))
    ;
