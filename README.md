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

Please add the following code to your Varnish configuration.

    #top level:
    # who is allowed to purge from cache
    # http://varnish-cache.org/trac/wiki/VCLExamplePurging
    acl purge {
            "127.0.0.1"; #localhost for dev purposes
            "10.0.11.0"/24; #server closed network
    }

    #in sub vcl_recv
    # purge if client is in correct ip range
    if (req.request == "PURGE") {
        if (!client.ip ~ purge) {
            error 405 "Not allowed.";
        }
        purge("req.url ~ " req.url);
        #log "PURGE " req.url;
        error 200 "Success";
    }

NOTE: this code invalidates the url for all domains. If your varnish serves multiple domains,
you should improve this configuration. Pull requests welcome :-)

The purger path invalidation is about equivalent to doing this:

     netcat localhost 6081 << EOF
     PURGE /url/to/purge HTTP/1.1
     host: webapp-host.name

     EOF

To use the varnish cache purger helper you must inject the ``liip_cache_control.purger`` service
or fetch it from the service container:

    // using a "manual" url
    $purger = $this->container->get('liip_cache_control.purger');
    $purger->invalidatePath('http://www.liip.ch/some/path');

    // using the router to generate the url
    $router = $this->container->get('router');
    $purger = $this->container->get('liip_cache_control.purger');
    $purger->invalidatePath($router->generate('myRouteName'));
