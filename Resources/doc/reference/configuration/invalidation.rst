invalidation
============

Configure :ref:`invalidation<invalidation configuration>` to invalidate
routes when some other routes are requested.

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        invalidation:
            enabled: true    # Defaults to 'auto'
            rules:
                -
                    match:
                        attributes:
                            _route: "villain_edit|villain_delete"
                    routes:
                        villains_index: ~    # e.g., /villains
                        villain_details:     # e.g., /villain/{id}
                            ignore_extra_params: false    # Defaults to true

.. include:: /includes/enabled.rst

rules
-----

**type**: ``array``

A set of invalidation rules. Each rule consists of a match definition and
one or more routes that will be invalidated. Rules are checked in the order
specified, where the first match wins. The routes are invalidated when:

1. the HTTP request matches *all* criteria defined under ``match``
2. the HTTP response is successful.

.. include:: /includes/match.rst

routes
^^^^^^

**type**: ``array``

A list of route names that will be invalidated.

ignore_extra_params
"""""""""""""""""""

**type**: ``boolean`` **default**: ``true``

Parameters from the request are mapped by name onto the route to be
invalidated. By default, any request parameters that are not part of the
invalidated route are ignored. Set ``ignore_extra_params`` to ``false``
to set those parameters anyway.

A more detailed explanation:
assume route ``villain_edit`` resolves to ``/villain/{id}/edit``.
When a client successfully edits the details for villain with id 123 (at
``/villain/123/edit``), the index of villains (at ``/villains``) can be
invalidated (purged) without trouble. But which villain details page should we
purge? The current request parameters are automatically matched against
invalidate route parameters of the same name. In the request to
``/villain/123/edit``, the value of the ``id`` parameter is ``123``. This value
is then used as the value for the ``id`` parameter of the ``villain_details``
route. In the end, the page ``villain/123`` will be purged.
