User Context
============

If your application serves different content depending on the user’s group
or context (guest, editor, admin), you can use the UserContextSubscriber.
Each user context gets its own unique hash, which is then used to vary the
content on. The event subscriber responds to hash requests and sets the Vary
header. This way, you will not have to store caches for each individual user.

.. note::

    Please read the :ref:`User Context <foshttpcache:user-context>`
    chapter in the FOSHttpCache documentation before continuing.

Configuration
-------------

First you need to set up your caching proxy as explained in the
:ref:`user context documentation <foshttpcache:user-context>`.

Then add the route you specified in the hash lookup request to the Symfony2
routing configuration, so that the user context event subscriber can get
triggered:

.. code-block:: yaml

    # app/config/routing.yml
    user_context_hash:
        /user-context-hash

.. caution::

    If you are using `Symfony2 security <http://symfony.com/doc/current/book/security.html>`_,
    for the hash generation, make sure that this route is inside the firewall
    for which you are doing the cache groups.

Then you can enable the subscriber with the default settings:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        user_context:
            enabled: true

Generating Hashes
-----------------

When a context hash request is received, a ``HashGenerator`` is used to build
the context information. You can implement your own providers or configure the
provided role provider that adds the Symfony roles of the current user.

Role Provider
~~~~~~~~~~~~~

One of the most common scenarios is to differentiate the content based on the
roles of the user. This bundle provides a service for this. It is disabled by
default. Enable it with:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache
        user_context:
            role_provider: true

Implement a Custom Context Provider
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Custom providers need to:

* implement ``FOS\HttpCache\UserContext\ContextProviderInterface``
* be tagged with ``fos_http_cache.user_context_provider``.

The ``updateUserContext`` method is called when the hash needs to be generated.

.. code-block:: yaml

    acme.demo_bundle.my_service:
      class: "%acme.demo_bundle.my_service.class%"
      tags:
        - { name: fos_http_cache.user_context_provider }

.. code-block:: xml

    <service id="acme.demo_bundle.my_service" class="%acme.demo_bundle.my_service.class%">
        <tag name="fos_http_cache.user_context_provider" />
    </service>

