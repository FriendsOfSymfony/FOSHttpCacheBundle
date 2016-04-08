Symfony HttpCache
=================

Symfony comes with a built-in reverse proxy written in PHP, known as
``HttpCache``. While it is certainly less efficient
than using Varnish or Nginx, it can still provide considerable performance
gains over an installation that is not cached at all. It can be useful for
running an application on shared hosting for instance
(see the `Symfony HttpCache documentation`_).

You can use features of this library with the Symfony ``HttpCache``. The basic
concept is to use event subscribers on the HttpCache class.

.. warning::

    Symfony HttpCache support is currently limited to following features:

    * Purge
    * Refresh
    * User context

Extending the correct HttpCache
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of extending ``Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache``, your
``AppCache`` should extend ``FOS\HttpCacheBundle\SymfonyCache\EventDispatchingHttpCache``::

    require_once __DIR__.'/AppKernel.php';

    use FOS\HttpCacheBundle\SymfonyCache\EventDispatchingHttpCache;

    class AppCache extends EventDispatchingHttpCache
    {
    }

.. tip::

    If your class already needs to extend a different class, simply copy the event
    handling code from the ``EventDispatchingHttpCache`` into your ``AppCache`` class.
    The drawback is that you need to manually check whether you need to adjust your
    ``AppCache`` each time you update the FOSHttpCache library.

By default, the event dispatching cache kernel registers all subscribers it knows
about. You can disable subscribers, or customize how they are instantiated.

If you do not need all subscribers, or need to register some yourself to
customize their behavior, overwrite ``getOptions`` and return the right bitmap
in ``fos_default_subscribers``. Use the constants provided by the
``EventDispatchingHttpCache``::

    public function getOptions()
    {
        return array(
            'fos_default_subscribers' => self::SUBSCRIBER_NONE,
        );
    }

To register subscribers that you need to instantiate yourself, overwrite
``getDefaultSubscribers``::

    use FOS\HttpCache\SymfonyCache\UserContextSubscriber;

    // ...

    public function getDefaultSubscribers()
    {
        // get enabled subscribers with default settings
        $subscribers = parent::getDefaultSubscribers();

        $subscribers[] = new UserContextSubscriber(array(
            'session_name_prefix' => 'eZSESSID',
        ));

        $subscribers[] = new CustomSubscriber();

        return $subscribers;
    }

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

Subscribers
~~~~~~~~~~~

Each feature has its subscriber. Subscribers are provided by the FOSHttpCache_
library. You can find the documentation for the subscribers in the
:ref:`FOSHttpCache Symfony Cache documentation section <foshttpcache:symfony httpcache configuration>`.

.. _Symfony HttpCache documentation: http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy
