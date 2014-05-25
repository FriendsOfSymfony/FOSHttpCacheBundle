# User Context Listener

This listener is an implementation of [the user context in FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/user-context.md)
for Symfony2. We strongly advice you to read [this documentation](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/user-context.md)
before continuing.

This listener makes it possible to stop a request with a 200 "OK" for hash
requests right after the security firewall has finished. This is useful when
one uses Varnish while handling content that is different between users.

The response provided with the hash request contains the hash header (default to
`X-User-Context-Hash`) which can be used to render different content, based on
a user context (like roles for example).

To enable the user context listener:

``` yaml
# app/config.yml
fos_http_cache:
    user_context:
        enable: true
        # Optional
        hash_header: X-User-Context-Hash
```

Your caching proxy needs to be configured to send a request with a special `Accept`
header and the user information (typically the session cookie). The response will
contain a special header field that holds the context information of this user.

This listener will add automatically add a Vary on `X-User-Context-Hash` on each Response.
So your content will now be tied to the user context.

## Validating a hash request

The user context listener use a request matcher is used to know if the listener
should treat the request as a hash one.

By default this request matcher will return true to every request containing
`application/vnd.fos.user-context-hash` in the 'Accept` header.

### Custom accept header and method

You can specify in the configuration another value for this accept header
and a optional request method to validate this hash request.

``` yaml
fos_http_cache:
    user_context:
        enable: true
        match:
            accept: 'application/vnd.foo.bar'
            method: HEAD
```

### Use your own service

If this implementation does not correspond to your need, you can create your own
request matcher by implementing the `Symfony\Component\HttpFoundation\RequestMatcherInterface`
interface in a service (e.g. `my_request_matcher`) and set this service in the
user context configuration:

``` yaml
fos_http_cache
    user_context:
        enable: true
        match:
            id: my_request_matcher
```

## Hash Request Cache

Response to the hash Request can be send with a ttl and a vary on specific headers.
In this example, we vary on the Cookie header and set a ttl of 15 minutes (900 seconds).

``` yaml
# app/config.yml
fos_http_cache:
    user_context:
        enable: true
        user_identifiers_header:
            - Cookie
        hash_cache_ttl: 900
```

## Add information to the user context

This bundle use the `HashGenerator` of the FOSHttpCache library, if you want to add
specific information in the Hash from a service, you need to implement the
`FOS\HttpCache\UserContext\ContextProviderInterface` interface in your service.

Then you can add the tag `fos_http_cache.user_context_provider` to your service. The
updateUserContext method will be called when the hash is generated.

``` xml
<service id="my_service" class="%my_service.class%">
    <tag name="fos_http_cache.user_context_provider" />
</service>
```

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
