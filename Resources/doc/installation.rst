Installation
============

This bundle is available on Packagist_. You can install it using Composer:

.. todo::

    Change version requirement to @stable.

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache-bundle:@alpha

Then add the bundle to your application:

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

.. _Packagist: https://packagist.org/packages/friendsofsymfony/http-cache-bundle

.. _requirements:

Requirements
------------

If you want to use this bundle’s annotations, include the
SensioFrameworkExtraBundle_ in your project:

.. code-block:: bash

    $ composer require sensio/framework-extra-bundle

If you wish to use expressions_ in your annotations , you also need Symfony’s
ExpressionLanguage_ component. If you’re not using full-stack Symfony 2.4 or
later, you need to explicitly add the component:

.. code-block:: bash

    $ composer require symfony/expression-language

Now you can configure the bundle under the ``fos_http_cache`` key as explained
in the :doc:`configuration/index` section.

.. _SensioFrameworkExtraBundle: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
.. _ExpressionLanguage: http://symfony.com/doc/current/components/expression_language/introduction.html
