Configuration
=============

.. toctree::
    :maxdepth: 2

    configuration/proxy-client
    configuration/rules
    configuration/headers
    configuration/invalidation
    configuration/tags
    configuration/user-context
    configuration/default





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


