Debug
=====

You can make this bundle sent a special header on each response to tell your
caching proxy that it should output debug information. You can then
:ref:`configure your caching proxy <foshttpcache:varnish_debugging>` to add
debug information when that header is present.

enabled
-------

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      debug:
        enabled: true

The default value is ``%kernel.debug%``, triggering the header when you are in
dev mode but not in prod mode.

header
------

You can change the header to use something else
than the default ``X-Cache-Debug`` value:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      debug_header: Please-Send-Debug-Infos
