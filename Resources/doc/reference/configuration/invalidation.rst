Invalidation Configuration
==========================

.. code-block:: yaml

        # Allows to disable the listener for invalidation annotations when your project does not use the annotations. Enabled by default if you have expression language and the cache manager.
        enabled:              ~ # One of true; false; "auto"

        # Set what requests should invalidate which target routes.
        rules:
            match: # Required

                # Request path.
                path:                 null

                # Request host name.
                host:                 null

                # Request HTTP methods.
                methods:

                    # Prototype
                    name:                 ~

                # List of client IPs.
                ips:

                    # Prototype
                    name:                 ~

                # Regular expressions on request attributes.
                attributes:

                    # Prototype
                    name:                 ~

                # Additional response HTTP status codes that will match.
                additional_cacheable_status:  []

                # Expression to decide whether response should be matched. Replaces HTTP code check and additional_cacheable_status.
                match_response:       []

            # Target routes to invalidate when request is matched
            routes:               # Required

                # Prototype
                name:
                    ignore_extra_params:  true

match
~~~~~

See ...

ignore_extra_params
~~~~~~~~~~~~~~~~~~~

Assume route ``villain_edit`` resolves to ``/villain/{id}/edit``. When a client
successfully edits the details for villain with id 123 (at
``/villain/123/edit``), the index of villains (at ``/villains``) can be
invalidated (purged) without trouble. But which villain details page should we
purge? The current request parameters are automatically matched against
invalidate route parameters of the same name. In the request to
``/villain/123/edit``, the value of the ``id`` parameter is ``123``. This value
is then used as the value for the `id` parameter of the `villain_details`
route. In the end, the page ``villain/123`` will be purged.

