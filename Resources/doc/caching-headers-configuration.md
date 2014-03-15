Caching Headers
===============

This bundle allows to configure `Cache-Control` and a couple of related headers
based on URL patterns. This part of the bundle is usable even when not using a
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
            host: ^login.example.com$
            controls: { public: false, max_age: 0, s_maxage: 0, last_modified: "-1 hour" }
            vary: [Accept-Encoding, Accept-Language]

        # match all actions of a specific controller
        -
            attributes: { _controller: ^AcmeBundle:Default:.* }
            controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }

        -
            host: ^/$
            controls: { public: true, max_age: 64000, s_maxage: 64000, last_modified: "-1 hour" }
            vary: [Accept-Encoding, Accept-Language]

        # match everything to set defaults
        -
            path: ^/
            controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }
```

Run ``app/console config:dump-reference fos_http_cache`` to get the list of all
configuration options.

Matching
--------

All matching criteria are regular expressions. If a request matches the
criteria the rule is applied. The filtering is done with the help of
`Symfony\Component\HttpFoundation\RequestMatcher`.

You can mix these criteria to have a rule only apply if all criteria match.

# path

For example, ``path: ^/`` will match every request. To only match the home
page, use ``path: ^/$``.

# host

`host` is a regular expression to limit the caching rules to specific hosts,
when you serve more than one host from this symfony application.

# methods

`methods` can be used to limit caching rules to specific HTTP methods like
GET requests.

# ips

`ips` is an array that can be used to limit the rules to a specified set of IP
addresses.

# attributes

`attributes` is an array to filter on route attributes. the most common use
case would be `_controller` when you need caching rules applied to a vendor
controller. Note that this is the controller name used in the route, so it
depends on your route configuration if you need `Bundle:Name:action` syntax
or the `service.id:method`.

Note that even for the request attributes, your criteria are interpreted as
regular expressions.

# unless_role

The ``unless_role`` makes it possible to skip rules based on whether the
current authenticated user is granted the provided role. If there is no
security in place, this filter will simply not be applied.

You could use this for example to never cache the requests by an admin.

Controls
--------

The `controls` part of the rule is set in a call to `Response::setCache();` if
the criteria match the request. The names are specified with underscores in
yml, but set with `-` in the `Cache-Control` header.

You can use the standard cache control directives:

* `etag`
* `last_modified`
* `max_age`
* `s_maxage`
* `private`
* `public`

``` yaml
fos_http_cache:
    rules:
        -
            path: ^/$
            controls:
                public: true
                max_age: 64000
                s_maxage: 64000
                last_modified: "-1 hour"
```

If you set `no_cache`, the header will simply be `Cache-Control: no-cache`
regardless of any options you might have configured or set previously in the
application.

``` yaml
fos_http_cache:
    rules:
        -
            path: ^/$
            controls:
                no_cache: true

The meaning of the headers are defined in detail in
(RFC 2616, HTTP/1.1)[http://tools.ietf.org/html/rfc2616#section-14.9].

You can also set headers that symfony considers non-standard, some coming from
RFCs extending HTTP/1.1. The following options are supported:

* must_revalidate (RFC 2616)[http://tools.ietf.org/html/rfc2616#section-14.9]
* proxy_revalidate (RFC 2616)[http://tools.ietf.org/html/rfc2616#section-14.9]
* no_transform (RFC 2616)[http://tools.ietf.org/html/rfc2616#section-14.9]
* stale_if_error: seconds (RFC 5861)[http://tools.ietf.org/html/rfc5861]
* stale_while_revalidate: seconds (RFC 5861)[http://tools.ietf.org/html/rfc5861]

The stale controls need a parameter specifying the seconds the cache is allowed
to continue serving stale content if needed. The other controls are flags that
are simply present or not, but have no value.

``` yaml
# app/config.yml
fos_http_cache:
    rules:
        -
            path: /,
            controls:
                stale_while_revalidate: 9000
                stale_if_error: 3000
                must_revalidate: false
                proxy_revalidate: true
```


Extra headers
-------------

# Vary

You can set the `vary` option to an array to have the listener add a `Vary`
header if the rule matches. This will only add to existing Vary headers,
not replace any previously set Vary options.

# X-Reverse-Proxy-TTL for Custom Varnish Time-Outs

By default, Varnish uses the `s-maxage` of your `Cache-Control` header to know
how long it should cache a page. But by default, the s-maxage is also sent to
the client. If there is a cache on the client side, it will look at this header
and cache the page if it is `public`. This is sometimes not ideal, notably when
you do [explicit cache invalidation](cache-manager.md). You might want Varnish
to keep a page in cache for a long time, but intermediate caches should not
keep the page for long.

One option could be to set a high `s-maxage` for Varnish and simply remove
the `s-maxage` from Cache-Control with vcl. This is not a good solution however,
as then intermediate caches will not cache anything anymore.

This bundle helps you to build a better solution: You can specify the option
`reverse_proxy_ttl` in a rule to get a special header that you can then use in
VCL:

``` yaml
# app/config.yml
fos_http_cache:
    rules:
        -
            path: ^/$
            reverse_proxy_ttl: 600
            controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }
```

This example will add the header `X-Reverse-Proxy-TTL: 600` to your response.
Varnish by default knows nothing about this header. To make this solution work,
you need to extend your varnish `vcl_fetch` configuration:

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

Note that there is a beresp.ttl field in VCL but unfortunately it can only be
set to absolute values and not dynamically. Thus we have to use a C code
fragment.
