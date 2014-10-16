Symfony HttpCache
=================

Symfony comes with a built-in reverse proxy written in PHP, known as
``HttpCache``. It can be useful when one hosts a Symfony application on shared
hosting for instance
(see [HttpCache documentation](http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy).

If you use Symfony ``HttpCache``, you'll need to make your ``AppCache`` class
extend ``FOS\HttpCacheBundle\HttpCache`` instead of
``Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache``.

.. warning::

    Symfony HttpCache support is currently limited to following features:

    * User context

Class constants
---------------

``FOS\HttpCacheBundle\HttpCache`` defines constants that can easily be overriden
in your ``AppCache`` class:

.. code-block:: php

    use FOS\HttpCacheBundle\HttpCache;

    class AppCache extends HttpCache
    {
        /**
         * Overriding default value for SESSION_NAME_PREFIX
         * to use eZSESSID instead.
         */
        const SESSION_NAME_PREFIX = 'eZSESSID';
    }

User context
~~~~~~~~~~~~

.. note::

    For detailed information on user context, please read the
    `user context documentation page </features/user-context>`

* ``SESSION_NAME_PREFIX``: Prefix for session names. Must match your session
  configuration.
  Needed for caching correctly generated user context hash for each user session.

  **default**: ``PHPSESSID``

.. warning::

    If you have a customized session name, it is **very important** that this
    constant matches it.
    Session IDs are indeed used as keys to cache the generated use context hash.

    Wrong session name will lead to unexpected results such as having the same
    user context hash for every users,
    or not having it cached at all (painful for performance.

* ``USER_HASH_ACCEPT_HEADER``: Accept header value to be used to request the
  user hash to the backend application.
  It must match the one defined in FOSHttpCacheBundle's configuration (see below).

  **default**: ``application/vnd.fos.user-context-hash``

* ``USER_HASH_HEADER``: Name of the header the user context hash will be stored
  into.
  It must match the one defined in FOSHttpCacheBundle's configuration (see below).

  **default**: ``X-User-Context-Hash``

* ``USER_HASH_URI``: URI used with the forwarded request for user context hash
  generation.

  **default**: ``/_fos_user_context_hash``

* ``USER_HASH_METHOD``: HTTP Method used with the forwarded request for user
  context hash generation.

  **default**: ``GET``
