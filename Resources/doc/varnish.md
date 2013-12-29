Varnish
=======

This bundle is compatible with Varnish version 3.0 onwards. In order to use
this bundle with Varnish, you probably have to make changes to your Varnish
configuration.

Below you will find detailed Varnish configuration recommendations. For a quick
overview, have a look at [the configuration that we use for our functional
tests](Tests/Functional/Fixtures/varnish/fos.vcl).

Configuration and usage
-----------------------

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

### Purging

First make sure your Varnish is [configured for handling PURGE requests](https://www.varnish-cache.org/docs/3.0/tutorial/purging.html).
For example:

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

#### Usage

You can now invalidate a path or an absolute URL by calling the `purge` method:

```php
$varnish->purge('/my/path')
    ->purge('http://myapp.dev/absolute/url')
    ->flush();
```

### Banning

First configure your Varnish for handling [BAN requests](https://www.varnish-software.com/static/book/Cache_invalidation.html):

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

#### Usage

You can now invalidate all URLs matching a regular expression by using the
`ban` method:

For instance, to ban all .png files:

```php
$varnish->ban('.*png$')->flush();
```

To ban all HTML URLs that begin with `/articles/`:

```php
$varnish->ban('/articles/.*', 'text/html')->flush();
```

By default, URLs will be banned on all hosts. You can override this default and
specify for which hosts you want to invalidate:

```php
$varnish->ban('*.png$', null, 'example\.com')->flush();
```

### Refreshing

If you want to invalidate cached objects by [forcing a refresh](https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh),
add the following to your Varnish configuration:

```varnish
sub vcl_recv {
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
        set req.hash_always_miss = true;
    }
}
```

#### Usage

You can now refresh a path or an absolute URL by calling the `refresh` method:

```php
$varnish->refresh('/my/path')
    ->refresh('http://myapp.dev/absolute/url')
    ->flush();
```