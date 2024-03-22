tags
====

Create tag rules in your application configuration to set tags on responses
and invalidate them. See the :doc:`tagging feature chapter </features/tagging>`
for an introduction. Also have a look at :doc:`configuring the proxy client for cache tagging <proxy-client>`.

.. include:: /includes/enabled.rst

.. note::

    If you use a :doc:`proxy client that does not support tag invalidation </features/tagging>`,
    cache tagging is not possible.

    If you leave ``enabled`` on ``auto``, tagging will only be activated when
    using the Varnish or Symfony proxy client.

    When using the noop proxy client or a custom service, ``auto`` will also
    lead to tagging being disabled. If you want to use tagging in one of those
    cases, you need to explicitly enable tagging.

Enables tag attributes and rules. If you want to use tagging, it is recommended
that you set this to ``true`` so you are notified of missing dependencies and
incompatible proxies:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            enabled: true

``response_header``
-------------------

**type**: ``string`` **default**: ``X-Cache-Tags`` resp. ``xkey``

HTTP header that tags are stored in.

.. note::

    If you use :ref:`Varnish xkey system <config_varnish_tag_mode>`, (having
    ``proxy_client.varnish.tag_mode: purgekeys``), the response header defaults
    to ``xkey`` rather than ``X-Cache-Tags``. Do not change the header in that
    case, the xkey header name is hardcoded into the xkey vmod.

.. include:: /includes/expression-language.rst

Your custom expression functions can then be used in both the ``tag_expressions``
section of the tag configuration and ``Tag`` :ref:`attributes <tag>`.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            expression_language: app.expression_language

``max_header_value_length``
---------------------------

**type**: ``integer`` **default**: ``null``

By default, the generated response header will not be split into multiple headers.
This means that depending on the amount of tags generated in your application the
value of that header might become pretty long. This again might cause issues with
your web server, as it usually has a default maximum header length and will reject
the request if the header exceeds this value. Using this configuration key, you can
configure a maximum length **in bytes** which will split your value into multiple
headers. Note that you might have to update your proxy configuration because it
needs to be able to handle multiple headers instead of just one.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        tags:
            max_header_value_length: 4096

.. note::

    4096 bytes is generally a good choice because it seems like most web servers have
    a maximum value of 4 KB configured.

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
:ref:`expressions <requirements>`. Assume a route
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
