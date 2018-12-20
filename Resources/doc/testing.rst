Testing
=======

Testing your Application
------------------------

If you do not want to test caching proxy interactions during testing, you can
:ref:`use the Noop proxy client <configuration_noop_proxy_client>`. This
client implements all invalidation features but does nothing at all.

If you want to write integration tests that validate your caching code and
configuration against the actual caching proxy, have a look at the
:ref:`FOSHttpCache library’s docs <foshttpcache:testing your application>`.

Testing the FOSHttpCacheBundle
------------------------------

To run this bundle’s tests, clone the repository, install vendors, and invoke
PHPUnit:

.. code-block:: bash

    $ git clone https://github.com/FriendsOfSymfony/FOSHttpCacheBundle.git
    $ cd FOSHttpCacheBundle
    $ composer install
    $ vendor/bin/simple-phpunit
