test
====

Configures a proxy server and test client that can be used when
:doc:`testing your application against a caching proxy </features/testing>`.

.. code-block:: yaml

    // app/config/config_test.yml
    fos_http_cache:
        test:
            proxy_server:
                varnish:
                    config_file: /etc/varnish/your-config.vcl
                    port: 8080
                    binary: /usr/sbin/varnish
            client:
                varnish:
                    enabled: true
                nginx:
                    enabled: false

proxy_server
------------

Configures a service that can be used to start, stop and clear your caching
proxy from PHP. This service is meant to be used in integration tests; don’t
use it in production mode.

varnish
^^^^^^^

config_file
~~~~~~~~~~~

**type**: ``string`` **required**

Path to a VCL file. For example Varnish configurations, see
:ref:`foshttpcache:proxy-configuration`.

binary
~~~~~~

**type**: ``string`` **default**: ``varnishd``

Path to the proxy binary (if the binary is named differently or not available
in your PATH).

port
~~~~

**type**: ``integer`` **default**: ``6181``

Port the caching proxy server listens on.

ip
~~

**type**: ``string`` **default**: ``127.0.0.1``

IP the caching proxy server runs on.

nginx
^^^^^

config_file
~~~~~~~~~~~

**type**: ``string`` **required**

Path to an Nginx configuration file. For an example Nginx configuration, see
:ref:`foshttpcache:proxy-configuration`.

binary
~~~~~~

**type**: ``string`` **default**: ``nginx``

Path to the proxy binary.

port
~~~~

**type**: ``integer`` **default**: ``8080``

Port the caching proxy server listens on.

ip
~~

**type**: ``string`` **default**: ``127.0.0.1``

IP the caching proxy server runs on.

client
------

Configures the :ref:`proxy test client <test client>` for Varnish and/or Nginx.

**type**: ``array``

enabled
^^^^^^^

**type**: ``enum`` **default**: ``auto`` **options**: ``true``, ``false``, ``auto``

The default value is ``%kernel.debug%``, enabling the client when you are in
test or dev mode but not in prod mode.

cache_header
------------

**type**: ``string`` **default**: ``X-Cache``

HTTP header that shows whether the response was a cache hit (``HIT``) or
a miss (``MISS``). This header must be :ref:`set by your caching proxy <foshttpcache:proxy-configuration>`
for the test assertions to work.
