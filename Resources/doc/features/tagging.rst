Tagging
=======

**Prerequisites**: :ref:`configure caching proxy for tagging <foshttpcache:varnish_tagging>`
(Varnish only).

If your application has many intricate relationships between cached items,
which makes it complex to invalidate them by route, cache tagging will be
useful. It helps you with invalidating many-to-many relationships between
content items.

Cache tagging, or more precisely `Tagged Cache Invalidation`_, a simpler
version of `Linked Cache Invalidation`_ (LCI), allows you to:

* assign tags to your applications’s responses (e.g., ``articles``, ``article-42``)
* :ref:`invalidate the responses by tag <foshttpcache:tags>` (e.g., invalidate
  all responses that are tagged ``article-42``)

Basic Configuration
-------------------

First :ref:`configure your proxy <foshttpcache:varnish_tagging>` for tagging.
Then enable tagging in your application configuration:

.. code-block:: yaml

    fos_http_cache:
      cache_manager:
        tags:
          enabled: true

For more information, see :doc:`/reference/configuration/tags`.

Setting and Invalidating Tags
-----------------------------

You can tag responses in three ways: with the cache manager, configuration and
annotations.

Cache Manager
~~~~~~~~~~~~~

Use ``tagResponse($response, $tags)`` to set tags on a response::

    use Symfony\Component\HttpFoundation\Response;

    class NewsController
    {
        public function articleAction($id)
        {
            $response = new Response('Some news article');
            $this->cacheManager->tagResponse($response, array('news', 'news-' . $id));

            return $response;
        }
    }

Then call ``invalidateTags($tags)`` to invalidate the tags you set::

    class NewsController
    {
        // ...

        public function editAction($id)
        {
            // ...

            $this->cacheManager->invalidateTags(array('news-' . $id))->flush();

            // ...
        }
    }

See the :ref:`Cache Manager reference <cache_manager_tags>` for full details.

Configuration
~~~~~~~~~~~~~

Alternatively, you can :doc:`configure rules </reference/configuration/tags>`
for setting and invalidating tags:

.. code-block:: yaml

    // app/config/config.yml
    fos_http_cache:
        tags:
            rules:
                -
                    match:
                        path: ^/news/article
                    tags: [news]

Now if a :term:`safe` request matches the criteria under ``match``, the response
will be tagged with ``news``. When an unsafe request matches, the tag ``news``
will be invalidated.

Annotations
~~~~~~~~~~~

Add the ``@Tag`` annotations to your controllers to set and invalidate tags::

    use FOS\HttpCacheBundle\Configuration\Tag;

    class NewsController
    {
        /**
         * @Tag("news", expression="'news-'~id")
         */
        public function articleAction($id)
        {
            // Assume $id equals 123
        }
    }

If ``articleAction`` handles a :term:`safe` request, a tag ``news-123`` is set
on the response. If a client tries to update or delete news article 123 with an
unsafe request to ``articleAction``, such as POST or DELETE, tag ``news-123``
is invalidated.

See the :ref:`@Tag reference <tag>` for full details.

.. _Tagged Cache Invalidation: http://blog.kevburnsjr.com/tagged-cache-invalidation
.. _Linked Cache Invalidation: http://tools.ietf.org/html/draft-nottingham-linked-cache-inv-03
.. _expressions: http://symfony.com/doc/current/components/expression_language/index.html
