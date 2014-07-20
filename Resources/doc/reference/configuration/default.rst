Default Configuration
=====================

Full Default Configuration
--------------------------

```yaml
fos_http_cache:
    cache_control:
        rules:
            match:                # Required

                # Request path.
                path:                 null

                # Request host name.
                host:                 null

                # Request HTTP methods.
                methods:

                    # Prototype
                    name:                 ~

                # List of client IPs.
                ips:

                    # Prototype
                    name:                 ~

                # Regular expressions on request attributes.
                attributes:

                    # Prototype
                    name:                 ~

                # Additional response HTTP status codes that will match.
                additional_cacheable_status:  []

                # Expression to decide whether response should be matched. Replaces HTTP code check and additional_cacheable_status.
                match_response:       null
            headers:              # Required

                # Add the specified cache control directives.
                cache_control:
                    max_age:              ~
                    s_maxage:             ~
                    private:              ~
                    public:               ~
                    must_revalidate:      ~
                    proxy_revalidate:     ~
                    no_transform:         ~
                    no_cache:             ~
                    stale_if_error:       ~
                    stale_while_revalidate:  ~

                # Set a default last modified timestamp if none is set yet. Value must be parseable by DateTime
                last_modified:        ~

                # Specify an X-Reverse-Proxy-TTL header with a time in seconds for a caching proxy under your control.
                reverse_proxy_ttl:    null

                # Define a list of additional headers on which the response varies.
                vary:                 []
    proxy_client:

        # If you configure more than one proxy client, specify which client is the default.
        default:              ~ # One of "varnish"; "nginx"
        varnish:

            # Addresses of the hosts varnish is running on. May be hostname or ip, and with :port if not the default port 6081.
            servers:              # Required

                # Prototype
                name:                 ~

            # Default host name and optional path for path based invalidation.
            base_url:             null

            # Guzzle service to use for customizing the requests.
            guzzle_client:        null
        nginx:

            # Addresses of the hosts varnish is running on. May be hostname or ip, and with :port if not the default port 6081.
            servers:              # Required

                # Prototype
                name:                 ~

            # Default host name and optional path for path based invalidation.
            base_url:             null

            # Guzzle service to use for customizing the requests.
            guzzle_client:        null

            # Path to trigger the purge on nginx for different location purge.
            purge_location:       ''

    # Configure the cache manager. Needs a proxy_client to be configured.
    cache_manager:

        # Allows to disable the invalidation manager. Enabled by default if you configure a proxy client.
        enabled:              ~ # One of true; false; "auto"
    tags:

        # Allows to disable the listener for tag annotations when your project does not use the annotations. Enabled by default if you have expression language and the cache manager.
        enabled:              ~ # One of true; false; "auto"
        rules:
            match:                # Required

                # Request path.
                path:                 null

                # Request host name.
                host:                 null

                # Request HTTP methods.
                methods:

                    # Prototype
                    name:                 ~

                # List of client IPs.
                ips:

                    # Prototype
                    name:                 ~

                # Regular expressions on request attributes.
                attributes:

                    # Prototype
                    name:                 ~

                # Additional response HTTP status codes that will match.
                additional_cacheable_status:  []

                # Expression to decide whether response should be matched. Replaces HTTP code check and additional_cacheable_status.
                match_response:       null
            tags:                 []
            tag_expressions:      []
    invalidation:

        # Allows to disable the listener for invalidation annotations when your project does not use the annotations. Enabled by default if you have expression language and the cache manager.
        enabled:              ~ # One of true; false; "auto"

        # Set what requests should invalidate which target routes.
        rules:
            match:                # Required

                # Request path.
                path:                 null

                # Request host name.
                host:                 null

                # Request HTTP methods.
                methods:

                    # Prototype
                    name:                 ~

                # List of client IPs.
                ips:

                    # Prototype
                    name:                 ~

                # Regular expressions on request attributes.
                attributes:

                    # Prototype
                    name:                 ~

                # Additional response HTTP status codes that will match.
                additional_cacheable_status:  []

                # Expression to decide whether response should be matched. Replaces HTTP code check and additional_cacheable_status.
                match_response:       null

            # Target routes to invalidate when request is matched
            routes:               # Required

                # Prototype
                name:
                    ignore_extra_params:  true

    # Listener that returns the request for the user context hash as early as possible.
    user_context:
        enabled:              false
        match:

            # Service id of a request matcher that tells whether the request is a context hash request.
            matcher_service:      fos_http_cache.user_context.request_matcher

            # Specify the accept HTTP header used for context hash requests.
            accept:               application/vnd.fos.user-context-hash

            # Specify the HTTP method used for context hash requests.
            method:               null

        # Cache the response for the hash for the specified number of seconds. Setting this to 0 will not cache those responses at all.
        hash_cache_ttl:       0

        # List of headers that contains the unique identifier for the user in the hash request.
        user_identifier_headers:

            # Defaults:
            - Cookie
            - Authorization

        # Name of the header that contains the hash information for the context.
        user_hash_header:     X-User-Context-Hash

        # Whether to enable a provider that automatically adds all roles of the current user to the context.
        role_provider:        false

    # Activate the flash message listener that puts flash messages into a cookie.
    flash_message:
        enabled:              false

        # Name of the cookie to set for flashes.
        name:                 flashes

        # Cookie path validity.
        path:                 /

        # Cookie host name validity.
        host:                 null

        # Whether the cookie should only be transmitted over a secure HTTPS connection from the client.
        secure:               false

        # Whether the cookie will be made accessible only through the HTTP protocol.
        httpOnly:             true
    debug:

        # Whether to send a debug header with the response to trigger a caching proxy to send debug information. If not set, defaults to kernel.debug.
        enabled:              true

        # The header to send if debug is true.
        header:               X-Cache-Debug
```

Cache Header Rules
------------------

The :doc:`caching rules <caching-rules>` allow to configure cache headers based
on the request.

Proxy Client
------------

The :doc:`proxy client configuration <proxy-client>` tells the bundle how to
invalidate cached data with the caching proxy.

Cache Manager
-------------

The cache manager is used to interact with the caching proxy, providing
convenient abstractions.

Tags
----

Tags allow to use controller annotations and configuration rules to set a tag
header and invalidate tags.


Invalidator
-----------

Invalidators use controller annotations and configuration rules to invalidate
certain routes and paths when a route is matched.

.. todo::

    Config reference is missing.

User Context
------------

The :doc:`user context <../event-subscribers/user-context>` is a feature to
share cached data even for logged in users.

Flash Message Listener
----------------------

The :doc:`flash message listener <../event-subscribers/flash-message>` is a
tool to avoid rendering the flash message into the content of a page. It is
another building brick for caching logged in pages.

Debug
-----

The :doc:`debug options <debug>` can be used to control whether a special
header should be set to tell the caching proxy that it has to output debug
information.
