Symfony HttpCache
=================

Symfony comes with a built-in reverse proxy written in PHP, known as
``HttpCache``. While it is certainly less efficient
than using Varnish or Nginx, it can still provide considerable performance
gains over an installation that is not cached at all. It can be useful for
running an application on shared hosting for instance
(see the `Symfony HttpCache documentation`_).

You can use features of this library with the Symfony ``HttpCache``. The basic
concept is to use event listeners on the HttpCache class.

.. warning::

    Symfony HttpCache support is currently limited to following features:

    * Purge
    * Refresh
    * Cache Tags
    * User Context

    Generic ``BAN`` operations are not supported.

Event Dispatching HttpCache
~~~~~~~~~~~~~~~~~~~~~~~~~~~

You need to adjust your ``AppCache`` to support event handling and register the
cache event listeners for the functionality you want to use.

To adjust your cache kernel, follow the instructions in the :ref:`FOSHttpCache Symfony Cache documentation section <foshttpcache:symfony httpcache configuration>`.

.. warning::

    Since Symfony 2.8, the class cache (``classes.php``) is compiled even in
    console mode by an optional warmer (``ClassCacheCacheWarmer``). This can
    produce conflicting results with the regular web entry points, because the
    class cache may contain definitions (such as the subscribers above) that
    are loaded before the class cache itself; leading to redeclaration fatal
    errors.

    There are two workarounds:

    * Disable class cache warming in console mode with e.g. a compiler pass::

        $container->getDefinition('kernel.class_cache.cache_warmer')->clearTag('kernel.cache_warmer');

    * Force loading of all classes and interfaced used by the ``HttpCache`` in
      ``app/console`` to make the class cache omit those classes. The simplest
      way to achieve this is to call ``class_exists`` resp. ``interface_exists``
      with each of them.

Event Listeners
~~~~~~~~~~~~~~~

Each cache feature has its own event listener. The listeners are provided by
the FOSHttpCache_ library. You can find the documentation for those listeners
in the :ref:`FOSHttpCache Symfony Cache documentation section <foshttpcache:symfony httpcache configuration>`.

Optimization for Single Server Installations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If your application runs on one single server, you can use the kernel
dispatcher to directly call the ``HttpCache`` rather than sending an actual
web request. This is more efficient, and you don't need to configure the server
IP address.

The :ref:`FOSHttpCache Symfony Proxy Client documentation section <foshttpcache:proxy client symfony httpcache kernel dispatcher>`
explains how to adjust your bootstrap - you will need to do this in both
``public/index.php`` and ``bin/console``.

Once your bootstrapping is adjusted, set ``fos_http_cache.proxy_client.symfony.use_kernel_dispatcher: true``.

.. _Symfony HttpCache documentation: http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy
