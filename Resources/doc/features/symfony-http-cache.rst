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

Subscribers
~~~~~~~~~~~

Each feature has its subscriber. Subscribers are provided by the FOSHttpCache_
library. You can find the documentation for the subscribers in the
:ref:`FOSHttpCache Symfony Cache documentation section <foshttpcache:symfony httpcache configuration>`.

Prevent redeclaration error for Event class
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Under some circumstances this bundle loads a class which inherits from
class ``Symfony\Component\EventDispatcher\Event`` in early cache lookup phase. This
results in the following error message:

    Fatal error: Cannot redeclare class Symfony\Component\EventDispatcher\Event in app/cache/dev/classes.php on line ...

This error may occure if you have told the kernel to load class cache in your
``app/console`` script, by adding something like ``$kernel->loadClassCache()``.
To get around the error you can either stop using the class cache or adding this
line to your ``app/console``::

    class_exists('FOS\\HttpCache\\SymfonyCache\\CacheEvent');

directly below the inclusion of ``bootstrap.php.cache``.

.. _Symfony HttpCache documentation: http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy
