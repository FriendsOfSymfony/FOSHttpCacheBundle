Cache Authorization Listener
============================

This listener makes it possible to stop a request with a 200 "OK" for HEAD
requests right after the security firewall has finished. This is useful when
one uses Varnish while handling content that is not available for all users.

To enable the authorization listener:

``` yaml
# app/config.yml
fos_http_cache:
    authorization_listener: true
```

In this scenario on a cache hit, your proxy must be configured to send a HEAD
request before sending back the reply. Your application is used to validate the
authorization, but does not need to regenerate the content as it is already
cached.

Note this obviously means that it only works with path based Security. Any
additional security implemented inside the Controller, Services or templates is
be ignored.

A varnish configuration for this scenario could look like this:

```
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
            return (lookup);
        }
    }
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
        # Don't forget to set the content-length, as the HEAD response doesn't have any
        // (and the client might run into problems)
        if (req.request == "HEAD") {
            set beresp.http.content-length = "0";
        }

        return (pass);
    }
}
```
