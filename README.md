CacheControlBundle
==================

This Bundle provides a way to set path based cache expiration headers via the app configuration

Installation
============

    1. Add this bundle to your project as a Git submodule:

        $ git submodule add git://github.com/liip/CacheControlBundle.git vendor/bundles/Liip/CacheControlBundle

    2. Add the Liip namespace to your autoloader:

        // app/autoload.php
        $loader->registerNamespaces(array(
            'Liip' => __DIR__.'/../vendor/bundles',
            // your other namespaces
        ));

    3. Add this bundle to your application's kernel:

        // application/ApplicationKernel.php
        public function registerBundles()
        {
          return array(
              // ...
              new Liip\CacheControlBundle\LiipCacheControlBundle(),
              // ...
          );
        }

Cache control
=============

Simply configure as many paths as needed with the given cache control rules and/or the location
of the varnish reverse proxies:

    # app/config.yml
    liip_cache_control:
        rules:
            # the controls section values are used in a call to Response::setCache();
            - { path: /, controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" } }
        purger:
            domain: http://www.liip.ch
            varnishes: 10.0.0.10 10.0.0.11 # space character separated list of ips, or an array of ips
            port: 80  # port varnish is listening on for incoming web connections

Varnish purging
===============

To use the varnish cache purger helper you must inject the ``liip_cache_control.purger`` service
or fetch it from the service container:

    // using a "manual" url
    $purger = $this->container->get('liip_cache_control.purger');
    $purger->invalidatePath('http://www.liip.ch/some/path');

    // using the router to generate the url
    $router = $this->container->get('router');
    $purger = $this->container->get('liip_cache_control.purger');
    $purger->invalidatePath($router->generate('myRouteName'));
