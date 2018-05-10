tags
====

Create tag rules in your application configuration to set tags on responses
and invalidate them. See the :doc:`tagging feature chapter </features/tagging>`
for an introduction.

.. include:: /includes/enabled.rst

.. note::

    If you use a :doc:`proxy client that does not support tag invalidation </features/tagging>`,
    cache tagging is not possible.

    If you leave ``enabled`` on ``auto``, tagging will only be activated when
    using the Varnish or Symfony proxy client.

    When using the noop proxy client or a custom service, ``auto`` will also
    lead to tagging being disabled. If you want to use tagging in one of those
    cases, you need to explicitly enable tagging.

Enables tag annotations and rules. If you want to use tagging, it is recommended
that you set this to ``true`` so you are notified of missing dependencies and
incompatible proxies:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            enabled: true

``response_header``
----------

**type**: ``string`` **default**: ``X-Cache-Tags``

Custom HTTP header that tags are stored in.

.. include:: /includes/expression-language.rst

Your custom expression functions can then be used in both the ``tag_expressions``
section of the tag configuration and ``@tag`` :ref:`annotations<tag>`.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            expression_language: app.expression_language

``strict``
----------

**type**: ``boolean`` **default**: ``false``

Set this to ``true`` to throw an exception when an empty or null tag is added.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            strict: true

``rules``
---------

**type**: ``array``

Write your tagging rules by combining a ``match`` definition with a ``tags``
array.  Rules are checked in the order specified, where the first match wins.
These tags will be set on the response when all of the following are true:

.. include:: /includes/safe.rst

When the definition matches an unsafe request (so 2 is false), the tags will be
invalidated instead.

.. include:: /includes/match.rst

tags
^^^^

**type**: ``array``

Tags that should be set on responses to safe requests; or invalidated for
unsafe requests.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            rules:
                -
                    match:
                        path: ^/news
                    tags: [news-section]

``tag_expressions``
~~~~~~~~~~~~~~~~~~~

**type**: ``array``

You can dynamically refer to request attributes using
:ref:`expressions <expression language requirement>`. Assume a route
``/articles/{id}``. A request to path ``/articles/123`` will set/invalidate
tag ``articles-123`` with the following configuration:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            rules:
                -
                    match:
                        path: ^/articles
                    tags: [articles]
                    tag_expressions: ["'article-'~id"]

The expression has access to all request attributes and the request itself
under the name ``request``.

You can combine ``tags`` and ``tag_expression`` in one rule.
