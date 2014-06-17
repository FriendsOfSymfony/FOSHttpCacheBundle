FOSHttpCacheBundle
==================

This is the documentation for the `FOSHttpCacheBundle <https://github.com/FriendsOfSymfony/FOSHttpCacheBundle>`_.

The FOSHttpCacheBundle offers tools to improve HTTP caching with Symfony2:

* global configuration options to set caching headers based on path, controller
  and other aspects of the request
* services for the FOSHttpCache_ library to actively invalidate caching proxies
* additional tools

Contents
--------

.. toctree::
    :maxdepth: 2

    installation
    configuration/caching-rules
    configuration/proxy-client
    invalidation
    tagging
    event-subscribers
    testing

Reference
---------

.. toctree::
    :maxdepth: 2

    reference/cache-manager
    reference/annotations
    reference/glossary

Overview
--------

========================= ============================= ================================================ ==============================================
Functionality             Configuration                 Annotations                                      Manually
========================= ============================= ================================================ ==============================================
Set Cache-Control headers (SensioFrameworkExtraBundle)  :doc:`rules <configuration/caching-rules>`       (Symfony)
Tag and invalidate        :doc:`@Tag <tagging>`         :doc:`rules <configuration/caching-rules>`       :doc:`cache manager <reference/cache-manager>`
Invalidate routes         :ref:`invalidateroute`        :ref:`invalidators <invalidation configuration>` :doc:`cache manager <reference/cache-manager>`
Invalidate paths          :ref:`invalidatepath`          -                                               :doc:`cache manager <reference/cache-manager>`
========================= ============================= ================================================ ==============================================
