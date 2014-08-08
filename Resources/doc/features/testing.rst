Testing
=======

**Prerequisites**: :ref:`configure caching proxy <foshttpcache:proxy-configuration>`.
Your application must be reachable from the caching proxy through HTTP, so you
need to have a web server running. If you already have a web server installed
for development, you can use that. Alternatively, on PHP 5.4 or newer, you can
use PHP’s built-in web server, for instance through
``FOS\HttpCache\Tests\Functional\WebServerListener``.

ProxyTestCase
-------------

If you wish to test your application caching and invalidation strategies
against a live Varnish or Nginx instance, extend your test classes from
``ProxyTestCase``. ``ProxyTestCase`` is an abstract base test class that
in its turn extends Symfony’s ``WebTestCase``. It offers some convenience
methods for cache testing::

    class YourTest extends ProxyTestCase
    {
        public function testCachingHeaders()
        {
            // Retrieve an URL from your application
            $response = $this->getResponse('/your/page');

            // Assert the response was a cache miss (came from the backend
            // application)
            $this->assertMiss($response);

            // Assume the URL /your/page sets caching headers. If we retrieve
            // it again, we should have a cache hit (response delivered by the
            // caching proxy):
            $response = $this->getResponse('/your/page');
            $this->assertHit($response);
        }
    }

.. _test client:

Test Client
^^^^^^^^^^^

The ``getResponse()`` method calls ``getHttpClient()`` to retrieve a test client. You
can use this client yourself to customise the requests. Note that the test
client must be :doc:`enabled in your configuration </reference/configuration/test>`.
By default, it is enabled when you access your application in debug mode and
you have :doc:`configured a proxy client </reference/configuration/proxy-client>`
with ``base_url``.

Controlling Your Caching Proxy
------------------------------

You can also use ``ProxyTestCase`` to control your caching proxy. First
configure the proxy server:

.. code-block:: yaml

    // app/config/config_test.yml
    fos_http_cache:
        test:
            proxy_server:
                varnish:
                    binary: /usr/sbin/varnishd
                    port: 8080
                    config_file: /etc/varnish/your-config.vcl

.. seealso:: :doc:`test configuration </reference/configuration/test>`.

The custom ``@clearCache`` PHPUnit annotation will start the proxy server
(if it was not yet running) and clear any previously cached content. This
enables you to write isolated test cases::

    use FOS\HttpCacheBundle\Test\ProxyTestCase;

    class YourTest extends ProxyTestCase
    {
        /**
         * @clearCache
         */
        public function testMiss()
        {
            // We can be sure this is a miss, because even if the content was
            // cached before, it has been cleared from the caching proxy.
            $this->assertMiss($this->getResponse('/your/page'));
        }
    }

You can annotate single test methods as well as classes with ``@clearCache``.
An annotated test class will restart and clear the caching proxy for each test
case contained in the class.

You can also manually control your caching proxy::

    use FOS\HttpCacheBundle\Test\ProxyTestCase;

    class YourTest extends ProxyTestCase
    {
        public function testMiss()
        {
            // Start caching proxy
            $this->getProxy()->start();

            // Clear proxy cache
            $this->getProxy()->clear();

            $this->assertMiss($this->getResponse('/your/page'));

            // Stop caching proxy
            $this->getProxy()->stop();
        }
    }

