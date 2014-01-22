Cache authorization listener
============================

Enable the authorization listener:

``` yaml
# app/config.yml
fos_http_cache:
    authorization_listener: true
```

This listener makes it possible to stop a request with a 200 "OK" for HEAD
requests right after the security firewall has finished. This is useful when
one uses Varnish while handling content that is not available for all users.

In this scenario on a cache hit, Varnish can be configured to issue a HEAD
request when this content is accessed. This way Symfony2 can be used to
validate the authorization, but no work needs to be made to regenerate the
content that is already in the Varnish cache.

Note this obviously means that it only works with path based Security. Any
additional security implemented inside the Controller will be ignored.

Note further that a HEAD response is supposed to contain the same HTTP header
meta data as the GET response to the same URL. However for the purpose of this
use case we have no other choice but to assume a 200.

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