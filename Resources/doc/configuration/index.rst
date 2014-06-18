Configuration
=============

Debug Header
------------

Enable the ``debug`` parameter to set a ``X-Cache-Debug`` header on each
response. You can then :ref:`configure your caching proxy <foshttpcache:varnish_debugging>`
to add debug information when that header is present:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      debug: true

The default value is ``%kernel.debug%``, triggering the header when you are in
dev mode but not in prod mode. You can change the header with the
``debug_header`` option:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      debug_header: Please-Send-Debug-Infos

Cache Header Rules
------------------

The :doc:`caching rules <caching-rules>` allow to configure cache headers based
on the request.

Proxy Client
------------

The :doc:`proxy client configuration <proxy-client>` tells the bundle how to
invalidate cached data with the caching proxy.

Cache Manager
-------------

The cache manager is used to interact with the caching proxy, providing
convenient abstractions.

.. todo::

    This doc is missing.

User Context
------------

The :doc:`user context <../event-subscribers/user-context>` is a feature to
share cached data even for logged in users.

Flash Message Listener
----------------------

The :doc:`flash message listener <../event-subscribers/flash-message>` is a
tool to avoid rendering the flash message into the content of a page. It is
another building brick for caching logged in pages.
