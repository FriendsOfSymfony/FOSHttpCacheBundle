Varnish
=======

This chapter describes how to configure Varnish to work with this bundle.

* [Introduction](#introduction)
* [Basic configuration](#basic-configuration)
  * [Basic Varnish configuration](#basic-varnish-configuration)
  * [Basic bundle configuration](#basic-varnish-configuration)
* [Purge](#purge)
* [Ban](#ban)
* [Tagging](#tagging)

Introduction
------------

This bundle is compatible with Varnish version 3.0 onwards. In order to use
this bundle with Varnish, you probably have to make changes to your Varnish
configuration.

Below you will find detailed Varnish configuration recommendations. For a quick
overview, have a look at [the configuration that is used for the bundle’s
functional tests](../../Tests/Functional/Fixtures/varnish/fos.vcl).

Basic configuration
-------------------

### Bundle configuration

```yaml
# app/config/config.yml

fos_http_cache:
  http_cache:
    varnish:
      ips: 123.123.123.1:6081, 123.123.123.2
      host: yourwebsite.com
```

* **host**: This must match the web host clients are using when connecting to varnish.
  You will not notice if this is mistyped, but cache invalidation will never happen.
* **ips**: List of IP addresses of your varnish servers. Comma separated.
* **port**: The port varnish is listening on for incoming web connections.

**TODO: MOVE** When using ESI, you will want to purge individual fragments. To generate the
corresponding ``_internal`` route, inject the ``http_kernel`` into your controller and
use HttpKernel::generateInternalUri with the parameters as in the twig
``render`` tag.

### Basic Varnish configuration

The bundle’s Varnish functionality requires the
[Guzzle HTTP client](http://docs.guzzlephp.org/en/latest/http-client/client.html)
to be installed:

```bash
$ composer require guzzle/http
```

If you wish to invalidate cached objects in Varnish, begin by adding an [ACL](https://www.varnish-cache.org/docs/3.0/tutorial/vcl.html#example-3-acls)
to your Varnish configuration. This ACL determines which IPs are allowed to
issue invalidation requests. Let’s call the ACL `invalidators`. The ACL below
will be used throughout the Varnish examples on this page.

```varnish
# /etc/varnish/your_varnish.vcl

acl invalidators {
  "localhost";
  # Add any other IP addresses that your Symfony2 app runs on and that you
  # want to allow invalidation requests from. For instance:
  # "192.168.1.0"/24;
}
```

Note: please make sure that all web servers running your Symfony2 app that may
trigger invalidation are whitelisted here. Otherwise, lost cached invalidation
requests will lead to lots of confusion.

### Purge

To configure Varnish for [handling PURGE requests](https://www.varnish-cache.org/docs/3.0/tutorial/purging.html):

```varnish
# /etc/varnish/your_varnish.vcl

sub vcl_recv {
  if (req.request == "PURGE") {
    if (!client.ip ~ invalidators) {
      error 405 "PURGE not allowed";
    }
    return (lookup);
  }
}

sub vcl_hit {
  if (req.request == "PURGE") {
    purge;
    error 200 "Purged";
  }
}

sub vcl_miss {
  if (req.request == "PURGE") {
    purge;
    error 404 "Not in cache";
  }
}
```

### Ban

To configure Varnish for [handling BAN requests](https://www.varnish-software.com/static/book/Cache_invalidation.html):

```varnish
# /etc/varnish/your_varnish.vcl

sub vcl_recv {
  if (req.request == "BAN") {
      if (!client.ip ~ invalidators) {
          error 405 "Not allowed.";
      }
      ban("obj.http.x-host ~ " + req.http.x-ban-host + " && obj.http.x-url ~ " + req.http.x-ban-url + " && obj.http.x-content-type ~ " + req.http.x-ban-content-type);
      error 200 "Banned";
  }
}

# Set BAN lurker friendly tags on object
sub vcl_fetch {
  set beresp.http.x-url = req.url;
  set beresp.http.x-host = req.http.host;
  set beresp.http.x-content-type = req.http.content-type;
}

# Remove tags when delivering to client
sub vcl_deliver {
  unset resp.http.x-url;
  unset resp.http.x-host;
  unset resp.http.x-content-type;
}
```

### Refresh

If you want to invalidate cached objects by [forcing a refresh](https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh),
add the following to your Varnish configuration:

```varnish
sub vcl_recv {
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
        set req.hash_always_miss = true;
    }
}
```

### Tagging

Add the following to your Varnish configuration to enable [cache tagging](tagging.md).

```varnish
sub vcl_recv {
    # ...

    if (req.request == "BAN") {
        # ...
        if (req.http.x-cache-tags) {
            ban("obj.http.host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            ban("obj.http.host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        error 200 "Banned";
    }
}
```