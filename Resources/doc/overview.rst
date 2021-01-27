Overview
========

Installation
------------

This bundle is available on Packagist_. You can install it using Composer. Note
that the FOSHttpCache_ library needs a ``psr/http-message-implementation`` and
``php-http/client-implementation``. If your project does not contain one,
composer will complain that it did not find ``psr/http-message-implementation``.

To install the bundle together with Symfony HttpClient, run:

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache-bundle symfony/http-client nyholm/psr7 guzzlehttp/promises

If you want to use something else than Symfony HttpClient, see Packagist for a list of
available `client implementations`_.

If you use an old version of Symfony, you
must manually register the bundle to your application:

.. code-block:: php

    <?php
    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new FOS\HttpCacheBundle\FOSHttpCacheBundle(),
            // ...
        );
    }

For most features, you also need to :ref:`configure a caching proxy <foshttpcache:proxy-configuration>`.

.. _requirements:

Requirements
------------

SensioFrameworkExtraBundle
~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to use this bundle’s annotations, install the
SensioFrameworkExtraBundle_:

.. code-block:: bash

    $ composer require sensio/framework-extra-bundle

And , if you don't use a recent version of Symfony, include it in your project::

     <?php
    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new FOS\HttpCacheBundle\FOSHttpCacheBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            // ...
        );

.. _expression language requirement:

ExpressionLanguage
~~~~~~~~~~~~~~~~~~

If you wish to use expressions_ in your annotations , you also need Symfony’s
ExpressionLanguage_ component. If you’re not using full-stack Symfony 2.4 or
later, you need to explicitly add the component:

.. code-block:: bash

    $ composer require symfony/expression-language

Configuration
-------------

Now you can configure the bundle under the ``fos_http_cache`` key. The
:doc:`features` section introduces the bundle’s features. The
:doc:`reference/configuration` section lists all configuration options.

Functionality
-------------

This table shows where you can find specific functions.

========================= ==================================== ==================================================== ==============================================
Functionality             Annotations                          Configuration                                        Manually
========================= ==================================== ==================================================== ==============================================
Set Cache-Control headers (SensioFrameworkExtraBundle_)        :doc:`rules <reference/configuration/headers>`       (Symfony_)
Tag and invalidate        :doc:`@Tag </features/tagging>`      :doc:`rules <reference/configuration/headers>`       :doc:`cache manager <reference/cache-manager>`
Invalidate routes         :ref:`invalidateroute`               :ref:`invalidators <invalidation configuration>`     :doc:`cache manager <reference/cache-manager>`
Invalidate paths          :ref:`invalidatepath`                :ref:`invalidators <invalidation configuration>`     :doc:`cache manager <reference/cache-manager>`
========================= ==================================== ==================================================== ==============================================

License
-------

This bundle is released under the MIT license.

.. literalinclude:: ../../LICENSE
    :language: none

.. _Packagist: https://packagist.org/packages/friendsofsymfony/http-cache-bundle
.. _SensioFrameworkExtraBundle: https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
.. _ExpressionLanguage: https://symfony.com/doc/current/components/expression_language.html
.. _Symfony: https://symfony.com/doc/current/http_cache.html#the-cache-control-header
.. _client implementations: https://packagist.org/providers/php-http/client-implementation
