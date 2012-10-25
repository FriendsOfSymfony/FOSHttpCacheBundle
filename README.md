CacheControlBundle
==================

This Bundle provides a way to set path based cache expiration headers via the app configuration and provides
a helper to control the reverse proxy varnish.

This bundle works with Symfony 2.0 and 2.1.

[![Build Status](https://secure.travis-ci.org/liip/LiipCacheControlBundle.png)](http://travis-ci.org/liip/LiipCacheControlBundle)

Installation with Symfony 2.0
=============================

STEP 1: There are two way to add the bundle to your project:

a) With the vendor script (preferred method):

Add the following lines to your ``deps`` file:

```
[LiipCacheControl]
    git=git@github.com:liip/LiipCacheControlBundle.git
    target=bundles/Liip/CacheControlBundle
```

Then run the vendor script:

``` bash
$ ./bin/vendors install
```

b) As a git submodule

Add this bundle to your project as a Git submodule:

``` bash
$ git submodule add git://github.com/liip/LiipCacheControlBundle.git vendor/bundles/Liip/CacheControlBundle
```

STEP 2: Add the Liip namespace to your autoloader:

``` php
// app/autoload.php
$loader->registerNamespaces(array(
    'Liip' => __DIR__.'/../vendor/bundles',
    // your other namespaces
));
```

Installation with Symfony 2.1 and composer
==========================================

Just add the following line to your projects composer.json require section:

```
"liip/cache-control-bundle": "dev-master"
```


Enable the module
=================

Add this bundle to your application's kernel:

``` php
// application/ApplicationKernel.php
public function registerBundles()
{
  return array(
      // ...
      new Liip\CacheControlBundle\LiipCacheControlBundle(),
      // ...
  );
}
```

Cache control
=============

Simply configure as many paths as needed with the given cache control rules:

``` yaml
# app/config.yml
liip_cache_control:
    rules:
        # the controls section values are used in a call to Response::setCache();
        - { path: ^/, controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }, vary: [Accept-Encoding, Accept-Language] }

        # only match login.example.com
        - { domain: ^login.example.com$, controls: { public: false, max_age: 0, s_maxage: 0, last_modified: "-1 hour" }, vary: [Accept-Encoding, Accept-Language] }
```

The matches are tried from top to bottom, the first match is taken and applied.


About the path parameter
------------------------

The ``path`` and ``domain`` parameter of the rules represent a regular expression that a page must match to use the rule.

For this reason, and it's probably not the behaviour you'd have expected, the path ```^/``` will match any page.

If you just want to match the homepage you need to use the path ```^/$```.

To match pages URLs with caching rules, this bundle uses the class ```Symfony\Component\HttpFoundation\RequestMatcher```.

Custom Varnish Parameters
------------------------

Additionally to the default supported headers, you may want to set custom caching headers for varnish.

``` yaml
# app/config.yml
liip_cache_control:
    rules:
        # the controls section values are used in a call to Response::setCache();
        - { path: /, controls: { stale_while_revalidate=9000, stale_if_error=3000, must-revalidate=false, proxy_revalidate=true } }
```

Custom Varnish Time-Outs
------------------------

Varnish checks the `Cache-Control` header of your response to set the TTL.
Sometimes you may want that varnish should cache your response for a longer
time than the browser. This way you can increase the performance by reducing
requests to the backend.

To achieve this you can set the `reverse_proxy_ttl` option for your rule:

``` yaml
# app/config.yml
liip_cache_control:
    rules:
        # the controls section values are used in a call to Response::setCache();
        - { path: /, reverse_proxy_ttl: 300, controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" } }
```

This example will add the header `X-Reverse-Proxy-TTL: 300` to your response.

But by default, varnish will not know anything about it. To get it to work
you have to extend your varnish `vcl_fetch` configuration:

```
sub vcl_fetch {

    /* ... */

    if (beresp.http.X-Reverse-Proxy-TTL) {
        C{
            char *ttl;
            ttl = VRT_GetHdr(sp, HDR_BERESP, "\024X-Reverse-Proxy-TTL:");
            VRT_l_beresp_ttl(sp, atoi(ttl));
        }C
        unset beresp.http.X-Reverse-Proxy-TTL;
    }

    /* ... */

}
```

Varnish will then look for the `X-Reverse-Proxy-TTL` header and if it exists,
varnish will use the found value as TTL and then remove the header.
There is a beresp.ttl field in VCL but unfortunately it can only be set to
absolute values and not dynamically. Thus we have to use a C code fragment.

Note that if you are using this, you should have a good purging strategy.

Varnish helper
==============

Configure the location of the varnish reverse proxies (be sure not to forget any, as each varnish must be purged):

``` yaml
# app/config.yml
liip_cache_control:
    varnish:
        domain: http://www.liip.ch
        ips: 10.0.0.10, 10.0.0.11 # comma separated list of ips, or an array of ips
        port: 80  # port varnish is listening on for incoming web connections
```

Purging
-------

Please add the following code to your Varnish configuration.

```
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
```

NOTE: this code invalidates the url for all domains. If your varnish serves multiple domains,
you should improve this configuration. Pull requests welcome :-)

The varnish path invalidation is about equivalent to doing this:

     netcat localhost 6081 << EOF
     PURGE /url/to/purge HTTP/1.1
     Host: webapp-host.name

     EOF

To use the varnish cache helper you must inject the ``liip_cache_control.varnish`` service
or fetch it from the service container:

``` php
// using a "manual" url
$varnish = $this->container->get('liip_cache_control.varnish');
/* $response Is an associative array with keys 'headers', 'body', 'error' and 'errorNumber' for each configured IP. 
   A sample response will look like:
   array('10.0.0.10' => array('body'    => 'raw-request-body',
                              'headers' => 'raw-headers',
                              'error'   =>  'curl-error-msg',
                              'errorNumber'   =>  integer-curl-error-number),
          '10.0.0.11' => ...)
*/
$response = $varnish->invalidatePath('/some/path');

// using the router to generate the url
$router = $this->container->get('router');
$varnish = $this->container->get('liip_cache_control.varnish');
$response = $varnish->invalidatePath($router->generate('myRouteName'));
```

When using ESI, you will want to purge individual fragments. To generate the
corresponding _internal route, inject the http_kernel into your controller and
use HttpKernel::generateInternalUri with the parameters as in the twig ``render``
tag.

Force refresh
-------------

Alternatively one can also force a refresh using the approach

```
#top level:
# who is allowed to purge from cache
# http://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh
acl refresh {
    "127.0.0.1"; #localhost for dev purposes
    "10.0.11.0"/24; #server closed network
}

sub vcl_hit {
    if (!obj.cacheable) {
        pass;
    }

    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ refresh) {
        set obj.ttl = 0s;
        return (restart);
    }
    deliver;
}
```

The vanish path force refresh is about equivalent to doing this:

    netcat localhost 6081 << EOF
    GET /url/to/refresh HTTP/1.1
    Host: webapp-host.name
    Cache-Control: no-cache, no-store, max-age=0, must-revalidate

    EOF

To use the varnish cache helper you must inject the ``liip_cache_control.varnish`` service
or fetch it from the service container:

``` php
// using a "manual" url
$varnish = $this->container->get('liip_cache_control.varnish');
$varnish->refreshPath('/some/path');
```

Cache authorization listener
============================

Enable the authorization listener:

``` yaml
# app/config.yml
liip_cache_control:
    authorization_listener: true
```

This listener makes it possible to stop a request with a 200 "OK" for HEAD requests
right after the security firewall has finished. This is useful when one uses Varnish while
handling content that is not available for all users.

In this scenario on a cache hit, Varnish can be configured to issue a HEAD request when this
content is accessed. This way Symfony2 can be used to validate the authorization, but no
work needs to be made to regenerate the content that is already in the Varnish cache.

Note this obviously means that it only works with path based Security. Any additional security
implemented inside the Controller will be ignored.

Note further that a HEAD response is supposed to contain the same HTTP header meta data as the
GET response to the same URL. However for the purpose of this use case we have no other choice
but to assume a 200.

```
backend default {
    .host = “127.0.0.1″;
    .port = “81″;
}

acl purge {
    “127.0.0.1″; #localhost for dev purposes
}

sub vcl_recv {
    # pipe HEAD requests as we convert all GET requests to HEAD and back later on
    if (req.request == “HEAD”) {
        return (pipe);
    }


    if (req.request == "GET") {
        if (req.restarts == 0) {
            set req.request = "HEAD";
            return (pass);
        } else {
            set req.http.Surrogate-Capability = "abc=ESI/1.0";
            return (lookup);
        }
    }
}

sub vcl_hash {
}

sub vcl_fetch {
    if (beresp.http.Cache-Control ~ “(private|no-cache|no-store)”) {
        return (pass);
    }

    if (beresp.status >= 200 && beresp.status < 300) {
        if (req.request == "HEAD") {
            # if the BE response said OK, change the request type back to GET and restart
            set req.request = "GET";
            restart;
        }
    } else {
        # In any other case (authentication 302 most likely), just pass the response to the client
        # Don't forget to set the content-length, as the HEAD response doesn't have any (and the client will hang)
        if (req.request == "HEAD") {
            set beresp.http.content-length = "0";
        }

        return (pass);
    }

    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        // varnish < 3.0:
        esi;
        // varnish 3.0 and later:
        // set beresp.do_esi = true;
    }
}
```

Flash message listener
======================

The Response flash message listener moves all flash messages currently set into a cookie. This
way it becomes possible to better handle flash messages in combination with ESI. The ESI
configuration will need to ignore the configured cookie. It will then be up to the client
to read out the cookie, display the flash message and remove the flash message via javascript.

``` yaml
# app/config.yml
liip_cache_control:
    flash_message_listener:
        name: flashes
        path: /
        domain: null
        secure: false
        httpOnly: true
```
