Caching headers
===============

Simply configure as many paths as needed with the given cache control rules:

``` yaml
# app/config.yml
fos_http_cache:
    rules:
        # the controls section values are used in a call to Response::setCache();
        - { path: ^/, controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }, vary: [Accept-Encoding, Accept-Language] }

        # only match login.example.com
        - { host: ^login.example.com$, controls: { public: false, max_age: 0, s_maxage: 0, last_modified: "-1 hour" }, vary: [Accept-Encoding, Accept-Language] }

        # match a specific controller action
        - { controller: ^AcmeBundle:Default:index$, controls: { public: true, max_age: 15, s_maxage: 30, last_modified: "-1 hour" }, vary: [Accept-Encoding, Accept-Language] }

```

The matches are tried from top to bottom, the first match is taken and applied.

Run ``app/console config:dump-reference fos_http_cache`` to get the full list of configuration options.

About the path parameter
------------------------

The ``path``, ``host`` and ``controller`` parameter of the rules represent a regular
expression that a page must match to use the rule.

For this reason, and it's probably not the behaviour you'd have expected, the
path ``^/`` will match any page.

If you just want to match the homepage you need to use the path ``^/$``.

To match pages URLs with caching rules, this bundle uses the class
``Symfony\Component\HttpFoundation\RequestMatcher``.

The ``unless_role`` makes it possible to skip rules based on if the current
authenticated user has been granted the provided role.

Custom Varnish Parameters
-------------------------

Additionally to the default supported headers, you may want to set custom
caching headers for varnish.

``` yaml
# app/config.yml
fos_http_cache:
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
fos_http_cache:
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