General Configuration
=====================

There is one global option you can configure for this bundle.

``generate_url_type``
---------------------

**type**: ``enum`` **options**: ``auto`` or one of the constants in UrlGeneratorInterface

The ``$referenceType`` to be used when generating URLs to invalidate or refresh
from routes. If you use ``ABSOLUTE_PATH`` to only generate
paths, you need to configure the ``base_url`` on the proxy client. When set to
``auto``, the value is determined based on the configuration.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        generate_url_type: !php/const Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH
