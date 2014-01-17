Configuration
=============

* [Debug information](#debug-information)

Debug information
-----------------

The debug parameter adds a ``X-Cache-Debug`` header to each response that you
can use in your Varnish configuration.

``` yaml
# app/config.yml
fos_http_cache:
    debug: true
```

Add the following code to your Varnish configuration to have debug headers
added to the response if it is enabled:

```
#in sub vcl_deliver
# debug info
# https://www.varnish-cache.org/trac/wiki/VCLExampleHitMissHeader
if (resp.http.X-Cache-Debug) {
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
       set resp.http.X-Cache = "MISS";
    }
    set resp.http.X-Cache-Expires = resp.http.Expires;
} else {
    # remove Varnish/proxy header
    remove resp.http.X-Varnish;
    remove resp.http.Via;
    remove resp.http.X-Purge-URL;
    remove resp.http.X-Purge-Host;
}
```