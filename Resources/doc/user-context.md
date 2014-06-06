# User Context

User context requests are a way to cache content that depends on some
permissions but is not fully individual. The caching proxy makes a first
request to determine the **user context hash** and then does the real request
with that hash in the request (but also the original credentials of the current
user). Responses that depend only on that hash set the correct `Vary` header
and the cache can be shared between users that have the same context. Because
of the `Vary`, it will be save to cache the response even though it was created
for a logged in user.

This feature is built on the user context concept of the [FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache)
It is strongly recommended to read [the user context documentation](http://foshttpcache.readthedocs.org/en/latest/user-context.html)
before continuing, so that you understand clearly what you are doing.

This bundle provides an **event subscriber** for the context. It aborts
requests for the context hash right after the security firewall was applied and
replies with the hash in the header (by default `X-User-Context-Hash`). All
other responses are set to vary on the hash header.

Additionally, the bundle provides a service that builds the user context hash
from context providers.

## Configuring the User Context Event Subscriber

First you need to set up your caching proxy as explained in
[the user context documentation](http://foshttpcache.readthedocs.org/en/latest/user-context.html)

Then add the route you specified in the hash lookup request to the Symfony
routing configuration, so that the user context event subscriber can get
triggered:

```yaml
# app/config/routing.yml
user_context_hash:
    /user-context-hash
```

.. note::

    This route is never actually used, as the context event subscriber will act
    before a controller would be called. But the user context is handled only
    after security happened. Security in turn only happens after the routing.
    If the routing does not find a route, the request is aborted with a "not
    found" error.

.. caution::

    If you are using Symfony2 security, make sure that this route is route is
    [inside the firewall](http://symfony.com/doc/current/book/security.html) for
    which you are doing the cache groups.

Enable the event subscriber with:

```yaml
# app/config/config.yml
fos_http_cache:
    user_context:
        enabled: true
```

This will enable the subscriber with the default settings.

Note: Enabling happens automatically if you configure any of the options on the
user_context.

### Tweaking the Naming

You can configure a couple of things:

* `hash_header`: The header that will be used to communicate the context hash
  in the answer to the context hash request, and that every other response will
  `Vary` on;
* `match.accept`, `match.method`: Criteria to detect the request for getting
  the context hash. You can set `accept` to configure the `Accept` header and
  the `method` to configure the HTTP method;
* `match.matcher_service`: Instead of defining the accept header or HTTP
  method, can specify your own matcher service implementing
  `Symfony\Component\HttpFoundation\RequestMatcherInterface`. If you do, the
  other options in the `match` section are ignored.

The configuration with the *default values* looks like this:

``` yaml
# app/config.yml
fos_http_cache:
    user_context:
        hash_header: X-User-Context-Hash
        match:
            id: fos_http_cache.user_context.request_matcher
            accept: 'application/vnd.fos.user-context-hash'
            # could be HEAD or GET
            method: ~
```

Remember that you need to **synchronize these values** with your caching proxy.

### Context Hash Request Cache

Context hash responses can be configured with a time to live (ttl) and `Vary`
information. Usually it is enough to set `hash_cache_ttl`. But if you use other
headers than `Authorization` and `Cookies`, you need to also configure the
`user_identifier_headers` to list all headers the context depends on.

If the hash only depends on the `Authorization` header and should be cached for
15 minutes, configure:

``` yaml
# app/config.yml
fos_http_cache:
    user_context:
        user_identifier_headers:
            - Authorization
        hash_cache_ttl: 900
```

## The user context

When a context hash request is received, the `HashGenerator` is used to build
the context information. You can implement your own providers or configure the
provided role provider that adds the symfony roles of the current user.

### Role Provider

One of the most common scenarios is to differentiate the content based on the
roles of the user. This bundle provides a service for this. It is disabled by
default. Enable it with:

``` yaml
fos_http_cache
    user_context:
        role_provider: true
```

If there is a security context that can provide the roles, all roles are added
to the hash.

### Implement a Custom Context Provider

Custom providers need to implement `FOS\HttpCache\UserContext\ContextProviderInterface`
and are tagged with `fos_http_cache.user_context_provider`. The
`updateUserContext` method is called when the hash needs to be generated.

``` yaml
acme.demo_bundle.my_service:
    class: "%acme.demo_bundle.my_service.class%"
    tags:
        - { name: fos_http_cache.user_context_provider }
```

``` xml
<service id="acme.demo_bundle.my_service" class="%acme.demo_bundle.my_service.class%">
    <tag name="fos_http_cache.user_context_provider" />
</service>
```
