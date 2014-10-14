Caching Headers
===============

**Prerequisites**: *None*

You can configure HTTP caching headers based on request and response properties.
This configuration approach is more convenient than `manually setting cache headers`_
and an alternative to `setting caching headers through annotations`_.

Set caching headers under the ``cache_control`` configuration section,
which consists of a set of rules. When the request matches all criteria under
``match``, the headers under ``headers`` will be set on the response.

By default cache headers are not overwritten if already set.
Header overwrite can be forced either globally, setting ``defaults`` ``overwrite`` to true,
or on a per rule basis setting ``overwrite`` to true under ``headers`` settings.

For instance:

.. code-block:: yaml

    # app/config/config.yml
    fos_http_cache:
        cache_control:
            defaults:
                overwrite: false
            rules:
                # only match login.example.com
                -
                    match:
                        host: ^login.example.com$
                    headers:
                        overwrite: true
                        cache_control: { public: false, max_age: 0, s_maxage: 0 }
                        last_modified: "-1 hour"
                        vary: [Accept-Encoding, Accept-Language]

                # match all actions of a specific controller
                -
                    match:
                        attributes: { _controller: ^AcmeBundle:Default:.* }
                        additional_cacheable_status: [400]
                    headers:
                        cache_control: { public: true, max_age: 15, s_maxage: 30 }
                        last_modified: "-1 hour"

                -
                    match:
                        path: ^/$
                    headers:
                        cache_control: { public: true, max_age: 64000, s_maxage: 64000 }
                        last_modified: "-1 hour"
                        vary: [Accept-Encoding, Accept-Language]

                # match everything to set defaults
                -
                    match:
                      path: ^/
                    headers:
                        cache_control: { public: true, max_age: 15, s_maxage: 30 }
                        last_modified: "-1 hour"

See the :doc:`/reference/configuration/headers` configuration reference.

.. _manually setting cache headers: http://symfony.com/doc/current/book/http_cache.html#the-cache-control-header
.. _setting caching headers through annotations: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/cache.html

