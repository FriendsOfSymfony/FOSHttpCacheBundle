Flash message listener
======================

The Response flash message listener moves all flash messages currently set into
a cookie. This way it becomes possible to better handle flash messages in
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

If you do not want the flash message listener, you can disable it:

``` yaml
# app/config.yml
fos_http_cache:
    flash_message_listener:
        enabled: false
```
