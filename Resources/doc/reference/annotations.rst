Annotations
===========

Annotate your controller actions to invalidate routes and paths when those
actions are executed.

.. note::

    Make sure to :ref:`install the dependencies first <requirements>`.

.. _invalidatepath:

@InvalidatePath
---------------

Invalidate a path::

    use FOS\HttpCacheBundle\Configuration\InvalidatePath;

    /**
     * @InvalidatePath("/posts")
     * @InvalidatePath("/posts/latest")
     */
    public function editAction()
    {
    }

See :doc:`/features/invalidation` for more information.

.. _invalidateroute:

@InvalidateRoute
----------------

Invalidate a route with parameters::

    use FOS\HttpCacheBundle\Configuration\InvalidateRoute;

    /**
     * @InvalidateRoute("posts")
     * @InvalidateRoute("posts", params={"type" = "latest"})
     */
    public function editAction()
    {
    }

You can also use expressions_ in the route parameter values::

    /**
     * @InvalidateRoute("posts", params={"number" = "id"})
     */
    public function editAction(Request $request)
    {
        // Assume $request->attributes->get('id') returns 123
    }

Route ``posts`` will now be invalidated with value ``123`` for param ``number``.

See :doc:`/features/invalidation` for more information.

.. _tag:

@Tag
----

Set a tag::

    /**
     * @Tag("news-article")
     */
    public function showAction()
    {
        // ...
    }

Multiple tags are possible::

    /**
     * @Tag("news")
     * @Tag("news-list")
     */
    public function indexAction()
    {
        // ...
    }

If you prefer, you can combine tags in one annotation::

    /**
     * @Tag({"news", "news-list"})
     */

You can also use expressions_ in tags. This will set tag ``news-123`` on the
response::

    /**
     * @Tag(expression="'news-'~id")
     */
    public function showAction($id)
    {
        // Assume $id equals 123
    }

Or, using a `param converter`_::

    /**
     * @Tag(expression="'news-'~article.getId()")
     */
    public function showAction(Article $article)
    {
        // Assume $article->getId() returns 123
    }

.. todo::

Tagging with Annotations
------------------------

You can make this bundle tag your response automatically using the ``@Tag``
annotation. GET operations will lead to the response being tagged; modifying
operations like POST, PUT, or DELETE will lead to the tags being invalidated.

When ``indexAction()`` returns a successful response for a safe (GET or HEAD)
request, the response will get the tag ``news``. The tag is set in a custom
HTTP header (``X-Cache-Tags``, by default).

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

See :doc:`/features/tagging` for an introduction to Tagging.
