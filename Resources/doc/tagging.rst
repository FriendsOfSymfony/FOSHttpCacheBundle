Tagged Cache Invalidation
=========================

If your application has many intricate relationships between cached items,
which makes it complex to invalidate them by route, cache tagging may be
useful.

Cache tagging, or more precisely `Tagged Cache Invalidation`_, is a simpler
version of `Linked Cache Invalidation`_ (LCI).

Tagged Cache Invalidation allows you to:

* assign tags to your applications’s responses (e.g., ``articles``, ``article-42``)
* :ref:`invalidate the responses by tag <foshttpcache:tags>` (e.g., invalidate all responses that are tagged
  ``article-42``)

Basic Configuration
-------------------

.. note::

    You need to :ref:`configure your proxy <foshttpcache:varnish_tagging>` for tagging first.

The tag system is controlled by ``fos_http_cache.cache_manager.tag_listener.enabled``.
By default, this setting is on ``auto``, meaning tagging is activated if you have a
proxy client configured and you have ``symfony/expression-language`` available in
your project. If you use tagging, it is recommended to set enabled to true to
be notified if your setup is broken:

.. code-block:: yaml

    fos_http_cache:
      cache_manager:
        tag_listener:
          enabled: true

Tagging with the Cache Manager
------------------------------

See the :ref:`Cache Manager chapter <cache_manager_tags>` on how to manually
set and invalidate tags.

Tagging with Configuration
--------------------------

The ``rules`` section of the configuration can also be used to define cache tags
on paths. You can set either or both headers and tags for each match. For the
request matching rules, see the :ref:`match section <match>` in the Caching
Headers chapter.

When the request matches all criteria, the tags are applied. If the request was
a GET or HEAD request, the response will get the specified tags in its header.
On all other operations, those tags will be invalidated.

.. code-block:: yaml

    fos_http_cache:
      rules:
        -
          match:
          path: ^/news
          tags: [news-section]

When a request goes to any URL starting with news, e.g. ``/news/42``, the
response will be tagged with ``news-section`` (in addition to any tags set by
the code or through annotations).

When a POST goes to ``/news/3``, the tag ``news-section`` is invalidated, in
addition to any other invalidation requests done with the cache manager or
through annotations.

Tagging with Annotations
------------------------

You can make this bundle tag your response automatically using the ``@Tag``
annotation. GET operations will lead to the response being tagged; modifying
operations like POST, PUT, or DELETE will lead to the tags being invalidated.

.. note::

    The ``@Tag`` annotation has a dependency on the SensioFrameworkExtraBundle,
    so make sure to include that bundle in your project:

    .. code-block:: bash

        $ composer require sensio/framework-extra-bundle

A simple example might look like this::

    use FOS\HttpCacheBundle\Configuration\Tag;

    class NewsController extends Controller
    {
        /**
         * @Tag("news")
         */
        public function indexAction()
        {
            // ...
        }
    }

When ``indexAction()`` returns a successful response for a safe (GET or HEAD)
request, the response will get the tag ``news``. The tag is set in a custom
HTTP header (``X-Cache-Tags``, by default).

Multiple tags are possible::

    /**
     * @Tag("news")
     * @Tag("news-list")
     */
    public function indexAction()
    {
        // ...
    }

If you prefer, you can combine your tags in one annotation::

    /**
     * @Tag({"news", "news-list"})
     */

Expressions
~~~~~~~~~~~

You can also use expressions_ in tags.

.. note::

    Expressions have a dependency on Symfony’s ExpressionLanguage component, so
    make sure to include that in your project:

    .. code-block:: bash

        $ composer require symfony/expression-language

The annotation below will set tag ``news-123`` on the response::

    /**
     * @Tag(expression="'news-'~id")
     */
    public function showAction($id)
    {
        // Assume $id equals 123
    }

Or, using a `param converter`_::

    /**
     * @Tag(expression="'news-'~$article.id")
     */
    public function showAction(Article $article)
    {
        // Assume $article->getId() returns 123
    }

Invalidate tags
~~~~~~~~~~~~~~~

Invalidate with annotations works just the same. Annotate your modifying
actions just like you did when setting tags::

    use FOS\HttpCacheBundle\Configuration\Tag;

    class NewsController extends Controller
    {
        /**
         * @Tag(expression="'news-'~article.id")
         * @Tag("posts")
         */
        public function editAction(Article $article)
        {
            // Assume $article->getId() returns 123
        }
    }

Any non-safe request to the ``editAction`` that returns a successful response
will trigger invalidation of both the ``news`` and the ``news-123`` tags.

.. _Tagged Cache Invalidation: http://blog.kevburnsjr.com/tagged-cache-invalidation
.. _Linked Cache Invalidation: http://tools.ietf.org/html/draft-nottingham-linked-cache-inv-03
.. _expressions: http://symfony.com/doc/current/components/expression_language/index.html
.. _param converter: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
