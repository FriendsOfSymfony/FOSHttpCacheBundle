Annotations
===========

You can annotate your controller actions to configure routes and paths that
should be invalidated when that action is executed.

.. note::

    Make sure to :ref:`install the needed dependencies first <requirements>`.

.. _invalidatepath:

@InvalidatePath
---------------

.. code-block:: php

    use FOS\HttpCacheBundle\Configuration\InvalidatePath;

    /**
     * @InvalidatePath("/posts")
     * @InvalidatePath("/posts/latest")
     */
    public function editAction()
    {
    }

.. _invalidateroute:

@InvalidateRoute
----------------

.. code-block:: php

    use FOS\HttpCacheBundle\Configuration\InvalidateRoute;

    /**
     * @InvalidateRoute("posts")
     * @InvalidateRoute("posts", params={"type" = "latest"})
     */
    public function editAction()
    {
    }

You can also use expressions_ in the route parameter values:

.. code-block:: php

    /**
     * @InvalidateRoute("posts", params={"number" = "id"})
     */
    public function editAction(Request $request)
    {
        // Assume $request->attributes->get('id') returns 123
    }

Route ``posts`` will now be invalidated with value ``123`` for param ``number``.

@Tag
----

.. code-block:: php

    /**
     * @Tag(expression="'news-'~$article.id")
     */
    public function showAction(Article $article)
    {
        // Assume $article->getId() returns 123
    }

See :doc:`../tagging` for more information.
