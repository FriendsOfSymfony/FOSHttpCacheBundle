Configuration
=============

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

    Config reference is missing.

Tags
----

Tags allow to use controller annotations and configuration rules to set a tag
header and invalidate tags.

.. todo::

    Config reference is missing.

Invalidator
-----------

Invalidators use controller annotations and configuration rules to invalidate
certain routes and paths when a route is matched.

.. todo::

    Config reference is missing.

User Context
------------

The :doc:`user context <../event-subscribers/user-context>` is a feature to
share cached data even for logged in users.

Flash Message Listener
----------------------

The :doc:`flash message listener <../event-subscribers/flash-message>` is a
tool to avoid rendering the flash message into the content of a page. It is
another building brick for caching logged in pages.

Debug
-----

The :doc:`debug options <debug>` can be used to control whether a special
header should be set to tell the caching proxy that it has to output debug
information.
