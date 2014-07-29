Flash Message Configuration
===========================

The :doc:`flash message listener <../../features/helpers/flash-message>` is a
tool to avoid rendering the flash message into the content of a page. It is
another building brick for caching pages for logged in users.

.. code-block:: yaml

    # app/config.yml
    fos_http_cache:
        flash_message:
            enabled: true
            name: flashes
            path: /
            host: null
            secure: false

enabled
-------

**type**: ``boolean`` **default**: ``false``

This event subscriber is disabled by default. You can set enabled to true if
the default values for all options are good for you. When you configure any of
the options, the subscriber is automatically enabled.

name
----

**type**: ``string`` **default**: ``flashes``
Set the name of the cookie.


path
----

**type**: ``string`` **default**: ``/``

The cookie path to use.

host
----

**type**: ``string``

Set the host for the cookie, e.g. to share among subdomains.

secure
------

**type**: ``boolean`` **default**: ``false``

Whether the cookie may only be passed through HTTPS.
