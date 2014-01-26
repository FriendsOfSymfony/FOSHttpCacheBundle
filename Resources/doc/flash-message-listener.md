Flash Message Listener
======================

When flash messages are part of the content, you can not cache that page. This
listener will take out eventual flash messages from the session and put it into
a cookie. In combination with ESI, you can do something...

TODO

This way it becomes possible to better handle flash messages in
combination with ESI. The ESI configuration will need to ignore the configured
cookie. It will then be up to the client to read out the cookie, display the
flash message and remove the flash message via javascript.

``` yaml
# app/config.yml
fos_http_cache:
    flash_message_listener:
        name: flashes
        path: /
        host: null
        secure: false
        httpOnly: true
```
