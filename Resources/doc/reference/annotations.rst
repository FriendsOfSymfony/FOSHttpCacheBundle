Annotations
===========

Annotate your controller actions to invalidate routes and paths when those
actions are executed.

.. note::

    Annotations need the SensioFrameworkExtraBundle including registering the
    Doctrine AnnotationsRegistry. Some features also need the
    ExpressionLanguage. Make sure to
    :ref:`installed the dependencies first <requirements>`.

.. _invalidatepath:

@InvalidatePath
---------------

Invalidate a path::

    use FOS\HttpCacheBundle\Configuration\InvalidatePath;

    /**
     * @InvalidatePath("/articles")
     * @InvalidatePath("/articles/latest")
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
     * @InvalidateRoute("articles")
     * @InvalidateRoute("articles", params={"type" = "latest"})
     */
    public function editAction()
    {
    }

You can also use expressions_ in the route parameter values. This obviously
:ref:`requires the ExpressionLanguage component <requirements>`. To invalidate
route ``articles`` with the ``number`` parameter set to ``123``, do::

    /**
     * @InvalidateRoute("articles", params={"number" = {"expression"="id"}})
     */
    public function editAction(Request $request, $id)
    {
        // Assume $request->attributes->get('id') returns 123
    }

The expression has access to all request attributes and the request itself
under the name ``request``.

See :doc:`/features/invalidation` for more information.

.. _tag:

@Tag
----

You can make this bundle tag your response automatically using the ``@Tag``
annotation. :term:`Safe <safe>` operations like GET that produce a successful
response will lead to that response being tagged; modifying operations like
POST, PUT, or DELETE will lead to the tags being invalidated.

When ``indexAction()`` returns a successful response for a safe (GET or HEAD)
request, the response will get the tag ``news``. The tag is set in a custom
HTTP header (``X-Cache-Tags``, by default).

Any non-safe request to the ``editAction`` that returns a successful response
will trigger invalidation of both the ``news`` and the ``news-123`` tags.

Set/invalidate a tag::

    /**
     * @Tag("news-article")
     */
    public function showAction()
    {
        // ...
    }

``GET /news/show`` will

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

You can also use expressions_ in tags. This obviously
:ref:`requires the ExpressionLanguage component <requirements>`. The following
example sets the tag ``news-123`` on the Response::

    /**
     * @Tag(expression="'news-'~id")
     */
    public function showAction($id)
    {
        // Assume request parameter $id equals 123
    }

Or, using a `param converter`_::

    /**
     * @Tag(expression="'news-'~article.getId()")
     */
    public function showAction(Article $article)
    {
        // Assume $article->getId() returns 123
    }

See :doc:`/features/tagging` for an introduction to tagging.
If you wish to change the HTTP header used for storing tags, see
:doc:`/reference/configuration/tags`.

.. _param converter: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
