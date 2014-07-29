tags
====

Create tag rules in your application configuration to set tags on responses
and invalidate them.

.. include:: /includes/enabled.rst

Enables tag annotations and rules. If you want to use tagging, it is recommended
that you set this to ``true`` so you are notified of missing dependencies:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_manager:
            tags:
                enabled: true

header
------

**type**: ``string`` **default**: ``X-Cache-Tags``

Custom HTTP header that tags are stored in.

rules
-----

**type**: ``array``

Write your tagging rules by combining a ``match`` definition with a ``tags``
array.  Rules are checked in the order specified, where the first match wins.
These tags will be set on the response when all of the following are true:

.. include:: /includes/safe.rst

When the definition matches an unsafe request (so 2 is false), the tags will be
invalidated instead.

.. include:: /includes/match.rst

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            rules:
                -
                    match:
                        path: ^/news
                    tags: [news-section]

.. note::

    See further the :doc:`tagging feature description </features/tagging>`.

tags
^^^^

**type**: ``array``

Tags that should be set on responses to safe requests; or invalidated for
unsafe requests.
