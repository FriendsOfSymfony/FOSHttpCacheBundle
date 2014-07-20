Tag Rules
---------

The ``rules`` section of the configuration can also be used to define cache tags
on paths. You can set either or both headers and tags for each match. For the
request matching rules, see the :ref:`match section <match>` in the Caching
Headers chapter.

When the request matches all criteria, the tags are applied. If the request was
a GET or HEAD request, the response will get the specified tags in its header.
On all other operations, those tags will be invalidated.



When a request goes to any URL starting with news, e.g. ``/news/42``, the
response will be tagged with ``news-section`` (in addition to any tags set by
the code or through annotations).

When a POST goes to ``/news/3``, the tag ``news-section`` is invalidated, in
addition to any other invalidation requests done with the cache manager or
through annotations.

enabled
~~~~~~~

By default, tagging is enabled if you have
:doc:`configured a proxy client </reference/configuration/proxy-client>` and
``symfony/expression-language`` is available in your project. If you want
to use tagging, it is recommended to enable is explicitly so you are notified
of missing dependencies:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
      cache_manager:
        tags:
          enabled: true

header
~~~~~~

Name of the HTTP header that the tags are stored in. Defaults to ``X-Tags``.

rules
~~~~~

Define your tagging rules by combining a :ref:`match definition <match>` with a
``tags`` array:

.. code-block:: yaml

    fos_http_cache:
      tags:
        rule`docs:
          -
            match:
              path: ^/news
            tags: [news-section]

.. note::

    See further: doc:`/features/tagging`.
