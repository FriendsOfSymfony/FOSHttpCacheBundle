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

Configuration
=============

Simply configure as many paths as needed with the given cache controls:

    # app/config.yml
    liip_cache_control:
        rules:
            - { path: /, controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" } }


To use the varnish cache invalidator helper, you can define a service

    cacheInvalidator:
        class: Liip\CacheControlBundle\Helper\Varnish
        arguments:
            domain: www.liip.ch
            varnishes: 10.0.0.10 10.0.0.11     # space character separated list of ips
            port: 80  # port varnish is listening on for incoming web connections

In your code, you do something along the lines:

$cacheInvalidator->invalidatePath($router->generate('myRouteName'));
