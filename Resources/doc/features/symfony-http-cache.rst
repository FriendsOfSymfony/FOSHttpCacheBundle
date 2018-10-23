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

.. note::

    Symfony HttpCache support is currently limited to following features:

    * Purge
    * Refresh
    * Cache Tags
    * User Context

    Ban operations are not supported.

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

.. _symfony_http_cache_kernel_dispatcher:

Optimization for Single Server Installations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Normally, cache invalidation is done with a HTTP request to each cache server.
If your application runs on one single server, you can use the kernel
dispatcher to have PHP code call the ``HttpCache`` in the same PHP process,
rather than sending an actual web request. This is more efficient, and you
don't need to configure the server IP address.

For this to work, your kernel needs to implement the ``HttpCacheProvider``
interface and know about the cache kernel. The cache is implemented with the
decorator pattern and thus the application kernel does not normally know about
the cache. FOSHttpCacheBundle provides the ``HttpCacheAware`` trait to simplify
making your kernel capable of providing the cache.

The recommended way to wire things up is to instantiate the cache kernel in the
kernel constructor to guarantee consistent setup over all entry points. Adjust
your kernel like this::

    // src/AppKernel.php

    namespace App;

    use FOS\HttpCache\SymfonyCache\HttpCacheAware;
    use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
    use Symfony\Component\HttpKernel\Kernel;

    class AppKernel extends Kernel implements HttpCacheProvider
    {
        use HttpCacheAware;
        // ...

        public function __construct(...)
        {
            // ...
            $this->setHttpCache(new AppCache($this));
        }
    }

Now you need to adjust your front controller to use that cache instance rather
than creating one::

    // public/index.php

    use App\AppKernel;

    // ...

    $kernel = new AppKernel($env, $debug);
    if ('prod' === $env) {
        $kernel = $kernel->getHttpCache();
    }

.. warning::

    If you do not want to instantiate the cache kernel in your kernel
    constructor, you need to make sure it is always available and consistently
    configured. Notably, the ``bin/console`` must also have access to the
    kernel to support invalidation on the command line.

Once your bootstrapping is adjusted, set the configuration option
``fos_http_cache.proxy_client.symfony.use_kernel_dispatcher: true``.

.. _Symfony HttpCache documentation: https://symfony.com/doc/current/http_cache.html#symfony-reverse-proxy
