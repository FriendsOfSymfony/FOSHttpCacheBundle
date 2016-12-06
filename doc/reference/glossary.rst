Glossary
========

.. glossary::

    Cacheable
        A *response* is considered cacheable when the status code is one of
        200, 203, 300, 301, 302, 404, 410. This range of status codes can be
        extended with :ref:`additional_cacheable_status` or overridden with
        :ref:`match_response`.

    Safe
        A *request* is safe if its HTTP method is GET or HEAD. Safe methods
        only retrieve data and do not change the application state, and
        therefore can be served with a response from the cache.



