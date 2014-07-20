The Cache Manager
=================

Use the CacheManager to explicitly invalidate or refresh paths, URLs, routes or
headers.

By *invalidating* a piece of content, you tell your caching proxy to no longer
serve it to clients. When next requested, the proxy will fetch a fresh copy
from the backend application and serve that instead.

By *refreshing* a piece of content, a fresh copy will be fetched right away.

.. note::

    These terms are explained in more detail in
    :ref:`An Introduction to Cache Invalidation <foshttpcache:invalidation introduction>`.

.. _cache manager invalidation:

invalidatePath
--------------

.. important::

    Make sure to :ref:`configure your proxy <foshttpcache:proxy-configuration>` for purging first.

Invalidate a path::

    $cacheManager->invalidatePath('/users')->flush();

.. note::

    The ``flush()`` method is explained :ref:`below <flushing>`.

Invalidate a URL::

    $cacheManager->invalidatePath('http://www.example.com/users');

Invalidate a route::

    $cacheManager = $container->get('fos_http_cache.cache_manager');
    $cacheManager->invalidateRoute('user_details', array('id' => 123));

Invalidate a :ref:`regular expression <foshttpcache:invalidate regex>`::

    $cacheManager = $container->get('fos_http_cache.cache_manager');
    $cacheManager->invalidateRegex('.*', 'image/png', array('example.com'));

The cache manager offers a fluent interface::

    $cacheManager
        ->invalidateRoute('villains_index')
        ->invalidatePath('/bad/guys')
        ->invalidateRoute('villain_details', array('name' => 'Jaws')
        ->invalidateRoute('villain_details', array('name' => 'Goldfinger')
        ->invalidateRoute('villain_details', array('name' => 'Dr. No')
    ;

.. _cache manager refreshing:

Refreshing
----------

.. note::

    Make sure to :ref:`configure your proxy <foshttpcache:proxy-configuration>` for purging first.

Refresh a path::

    $cacheManager = $container->get('fos_http_cache.cache_manager');
    $cacheManager->refreshPath('/users');

Refresh a URL::

    $cacheManager = $container->get('fos_http_cache.cache_manager');
    $cacheManager->refreshPath('http://www.example.com/users');

Refresh a Route::

    $cacheManager = $container->get('fos_http_cache.cache_manager');
    $cacheManager->refreshRoute('user_details', array('id' => 123));

.. _cache_manager_tags:

tagResponse()
-------------

Use the Cache Manager to tag responses::

    // $response is a \Symfony\Component\HttpFoundation\Response object
    $cacheManager->tagResponse($response, array('some-tag', 'other-tag'));

The tags are appended to already existing tags, unless you set the ``$replace``
option to true::

    $cacheManager->tagResponse($response, array('different'), true);

invalidateTags()
----------------

Invalidate cache tags::

    $cacheManager->invalidateTags(array('some-tag', 'other-tag'));

.. _flushing:

Flushing
--------

Internally, the invalidation requests are queued and only sent out to your HTTP
proxy when the manager is flushed. The manager is flushed automatically at the
right moment:

* when handling a HTTP request, after the response has been sent to the client
  (Symfony’s `kernel.terminate event`_)
* when running a console command, after the command has finished (Symfony’s
  `console.terminate event`_).

You can also flush the cache manager manually::

    $cacheManager->flush();

.. _kernel.terminate event: http://symfony.com/doc/current/components/http_kernel/introduction.html#the-kernel-terminate-event
.. _console.terminate event: http://symfony.com/doc/current/components/console/events.html#the-consoleevents-terminate-event
