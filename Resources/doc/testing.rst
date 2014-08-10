Testing
=======

Testing the Bundle
------------------

To run this bundle’s tests, clone the repository, install vendors, and invoke
PHPUnit:

.. code-block:: bash

    $ git clone https://github.com/FriendsOfSymfony/FOSHttpCacheBundle.git
    $ cd FOSHttpCacheBundle
    $ composer install --dev
    $ phpunit

.. tip::

    See the :ref:`FOSHttpCache library’s docs <foshttpcache:testing your application>`
    on how to write integration tests that validate your caching code and
    configuration against a caching proxy.
