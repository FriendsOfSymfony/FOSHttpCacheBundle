cacheable
=========

``response``
------------

Configure which responses are considered :term:`cacheable`. This bundle will
only set Cache-Control headers, including tags etc., on cacheable responses.

.. _additional_status:

``additional_status``
^^^^^^^^^^^^^^^^^^^^^

**type**: ``array``

Following `RFC 7231`_, by default responses are considered :term:`cacheable`
if they have status code 200, 203, 204, 206, 300, 301, 404, 405, 410, 414 or 501.
You can add status codes to this list by setting ``additional_status``:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cacheable:
            response:
                additional_status:
                    - 100
                    - 500

``expression``
^^^^^^^^^^^^^^

**type**: ``string``

An ExpressionLanguage expression to decide whether the response is considered
cacheable. The expression can access the Response object with the response variable.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cacheable:
            response:
                expression: "response.getStatusCode() >= 300"

You cannot set both ``expression`` and ``additional_status``.

.. _RFC 7231: https://tools.ietf.org/html/rfc7231#section-6.1
