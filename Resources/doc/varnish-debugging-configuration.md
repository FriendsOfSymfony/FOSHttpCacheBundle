Configuring Varnish Debugging Information
=========================================

Enabling the debug parameter adds a ``X-Cache-Debug`` header to each response
that you can use in your Varnish configuration.

``` yaml
# app/config_integration.yml
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
    # remove Varnish/proxy header and ban helper headers
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.x-url;
    unset resp.http.x-host;
    unset resp.http.x-content-type;
}
```

Note: You normally do not want this enabled in your production environment.
