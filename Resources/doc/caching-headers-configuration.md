Caching Headers
===============

This bundle allows to configure `Cache-Control` and a couple of related headers
based on the request. This part of the bundle is usable even when not using a
caching proxy at all.

The configuration approach is more convenient than having all your controllers
set the cache rules on the response. The pattern are applied in the order
specified, taking the first match. An example configuration could look like
this:

``` yaml
# app/config.yml
fos_http_cache:
    rules:
        # only match login.example.com
        -
            match:
                host: ^login.example.com$
            headers:
                cache_control: { public: false, max_age: 0, s_maxage: 0, last_modified: "-1 hour" }
                vary: [Accept-Encoding, Accept-Language]

        # match all actions of a specific controller
        -
            match:
                attributes: { _controller: ^AcmeBundle:Default:.* }
            headers:
                cache_control: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }

        -
            match:
                path: ^/$
            headers:
                cache_control: { public: true, max_age: 64000, s_maxage: 64000, last_modified: "-1 hour" }
                vary: [Accept-Encoding, Accept-Language]

        # match everything to set defaults
        -
            match:
                path: ^/
            headers:
                cache_control: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }
```

match
-----

All matching criteria are regular expressions. If a request matches the
criteria and is "safe", the headers are set. The matching is done with
`Symfony\Component\HttpFoundation\RequestMatcher`, which combines the
criteria with an **and** logic.

By default, a response is only considered to be safe to cache if both
`Request::isMethodSafe()` (GET or HEAD request) and the status code is in the
list 200, 203, 300, 301, 302, 404, 410.

### path

For example, ``path: ^/`` will match every request. To only match the home
page, use ``path: ^/$``.

### host

`host` is a regular expression to limit the caching rules to specific hosts,
when you serve more than one host from this symfony application.

### methods

`methods` can be used to limit caching rules to specific HTTP methods like
GET requests.

### ips

`ips` is an array that can be used to limit the rules to a specified set of
request client IP addresses.

Note: If you are using a caching proxy, you need to be careful to not get
confused. If you have specific IPs that should see different headers, you need
to forward the client IP to the backend in the first place, as normally the
backend will always see the caching proxy IP. See
(Trusting Proxies)[http://symfony.com/doc/current/components/http_foundation/trusting_proxies.html]
in the Symfony documentation.

### attributes

`attributes` is an array to filter on route attributes. the most common use
case would be `_controller` when you need caching rules applied to a
controller. Note that this is the controller name used in the route, so it
depends on your route configuration whether you need `Bundle:Name:action`
syntax or `service.id:methodName`.

Note that even for the request attributes, your criteria are interpreted as
regular expressions.

### unless_role

The ``unless_role`` makes it possible to skip rules based on whether the
current authenticated user is granted the provided role. If there is no
security in place, this filter will simply not be applied.

You could use this for example to never cache the requests by an admin.

headers
-------

In this section, you can define what headers to set on the response if the
match was successful.

### cache_control

The map under `cache_control` is set in a call to `Response::setCache()`. The
names are specified with underscores in yml, but translated to `-` for the
`Cache-Control` header.

You can use the standard cache control directives:

* `max_age` time in seconds;
* `s_maxage` time in seconds for proxy caches (also public caches);
* `private` true or false;
* `public` true or false;
* `no_cache` true or false (use exclusively to support HTTP 1.0);

``` yaml
fos_http_cache:
    rules:
        -
            headers:
                cache_control:
                    public: true
                    max_age: 64000
                    s_maxage: 64000
```

If you use `no_cache`, you should **not set any other options**. This will make
Symfony properly handle HTTP 1.0, setting the `Pramga: no-cache` and
`Expires: -1` headers. If you add other cache_control options, Symfony will not
do this handling. Note that Varnish 3 does *not* respect no-cache by default.
If you want it respected, add your own logic to `vcl_fetch`.

The meaning of the cache-control headers are defined in detail in
(RFC 2616, HTTP/1.1)[http://tools.ietf.org/html/rfc2616#section-14.9].

#### Extra cache control directives

You can also set headers that symfony considers non-standard, some coming from
RFCs extending HTTP/1.1. The following options are supported:

* must_revalidate (RFC 2616)[http://tools.ietf.org/html/rfc2616#section-14.9]
* proxy_revalidate (RFC 2616)[http://tools.ietf.org/html/rfc2616#section-14.9]
* no_transform (RFC 2616)[http://tools.ietf.org/html/rfc2616#section-14.9]
* stale_if_error: seconds (RFC 5861)[http://tools.ietf.org/html/rfc5861]
* stale_while_revalidate: seconds (RFC 5861)[http://tools.ietf.org/html/rfc5861]

The *stale* directives need a parameter specifying the time in seconds how long
a  cache is allowed to continue serving stale content if needed. The other
directives are flags that are included when set to true.

``` yaml
# app/config.yml
fos_http_cache:
    rules:
        -
            path: /,
            controls:
                stale_while_revalidate: 9000
                stale_if_error: 3000
                must_revalidate: true
                proxy_revalidate: true
                no_transform: true
```

### last_modified

The input to the `last_modified` is used for the `Last-Modified` header. This
value must be a valid input to `DateTime`.

Note: This option will **only be set if no last modified information** is set
on the response yet.

fos_http_cache:
    rules:
        -
            headers:
                last_modified: "-1 hour"

### vary

You can set the `vary` option to an array that defines the contents of the
`Vary` header when matching the request. This adds to existing Vary headers,
keeping previously set Vary options.

### X-Reverse-Proxy-TTL for Custom Reverse Proxy Time-Outs

By default, reverse proxies use the `s-maxage` of your `Cache-Control` header
to know how long it should cache a page. But by default, the s-maxage is also
sent to the client. Any caches on the internet might look at `s-maxage` and
cache the page if it is `public`. This can be a problem, notably when you do
[explicit cache invalidation](cache-manager.md). You might want your reverse
proxy to keep a page in cache for a long time, but outside caches should not
keep the page for a long duration.

One option could be to set a high `s-maxage` for the proxy and simply rewrite
the response to remove or reduce the `s-maxage`. This is not a good solution
however, as you start to duplicate your caching rule definitions.

This bundle helps you to build a better solution: You can specify the option
`reverse_proxy_ttl` in the headers section to get a special header that you can
then use on the reverse proxy:

``` yaml
# app/config.yml
fos_http_cache:
    rules:
        -
            headers:
                reverse_proxy_ttl: 3600
                controls: { public: true, s_maxage: 60 }
```

This example adds the header `X-Reverse-Proxy-TTL: 3600` to your responses.
Varnish by default knows nothing about this header. To make this solution work,
you need to extend your varnish `vcl_fetch` configuration:

```
sub vcl_fetch {
    if (beresp.http.X-Reverse-Proxy-TTL) {
        C{
            char *ttl;
            ttl = VRT_GetHdr(sp, HDR_BERESP, "\024X-Reverse-Proxy-TTL:");
            VRT_l_beresp_ttl(sp, atoi(ttl));
        }C
        unset beresp.http.X-Reverse-Proxy-TTL;
    }
}
```

Note that there is a beresp.ttl field in VCL but unfortunately it can only be
set to absolute values and not dynamically. Thus we have to revert to a C code
fragment.
