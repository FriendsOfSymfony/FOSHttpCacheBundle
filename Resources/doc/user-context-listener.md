# User Context Listener

This listener is an implementation of [the user context in FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/user-context.md)
for Symfony2. We strongly advice you to read [this documentation](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/user-context.md)
before continuing.

This listener makes it possible to stop a request with a 200 "OK" for HEAD
requests right after the security firewall has finished. This is useful when
one uses Varnish while handling content that is not available for all users.

The response provided with the HEAD request contains a hash header (default to
`X-FOSHttpCache-Hash`) which can be used to render different content, based on
a user context (like roles for example).

To enable the user context listener:

``` yaml
# app/config.yml
fos_http_cache:
    user_context:
        enable: true
        # Optional
        hash_header: X-FOSHttpCache-Hash
```

Your proxy must be configured to send a HEAD request before sending back the reply.
Your application is used to validate the authentication and send a user context hash,
but may not need to regenerate the content as it can already be cached by another user
with the same context.

## HEAD Request Cache

Response to the HEAD Request can be send with a ttl and a vary on a specific header.

In this example, we vary on the Cookie header and set a ttl of 15 minutes (900 seconds).

``` yaml
# app/config.yml
fos_http_cache:
    user_context:
        enable: true
        vary_header: Cookie
        hash_cache_ttl: 900
```

By default the vary header is X-FOSHttpCache-SessionId, as the Cookie may not represent
a uniquer identifier for your user. If you use the default header, you have to define its
value in the HttpProxy before sending the HEAD request.

## Role Provider

One of the most common scenario is to render a view based on user roles.
FOSHttpCacheBundle give you a basic implementation of this scenario
with the `FOSHttpCacheBundle\UserContext\RoleProvider` class.

You can enable this provider with the following configuration:

``` yaml
fos_http_cache
    user_context:
        enable: true
        role_provider: true
```

If a token is available from the security context, all its roles will be added to the hash.
