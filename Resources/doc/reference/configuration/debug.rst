debug
=====

Enable the ``debug`` parameter to set a custom header (``X-Cache-Debug``)
header on each response. You can then
:ref:`configure your caching proxy <foshttpcache:varnish_debugging>` to add
debug information when that header is present:


.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        debug:
            enabled: true
            header: Please-Send-Debug-Infos

enabled
-------

**type**: ``enum`` **default**: ``auto`` **options**: ``true``, ``false``, ``auto``

The default value is ``%kernel.debug%``, triggering the header when you are in
dev mode but not in prod mode.

header
------

**type**: ``string`` **default**: ``X-Cache-Debug``

Custom HTTP header that triggers the caching proxy to set debugging information
on the response.
