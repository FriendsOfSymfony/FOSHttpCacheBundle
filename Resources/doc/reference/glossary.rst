Glossary
========

.. glossary::

    Cacheable
        According to `RFC 7231`_, a *response* is considered cacheable when its
        status code is one of 200, 203, 204, 206, 300, 301, 404, 405, 410, 414
        or 501.

    Safe
        A *request* is safe if its HTTP method is GET or HEAD. Safe methods
        only retrieve data and do not change the application state, and
        therefore can be served with a response from the cache.

.. _RFC 7231: https://tools.ietf.org/html/rfc7231#section-6.1
