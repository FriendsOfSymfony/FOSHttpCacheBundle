Flash Message Listener
======================

When flash messages are rendered into the content of a page, you can't cache
the page anymore. This listener reads all flash messages into a cookie, leading
to them not being there anymore when rendering the template. This will return
the page with a set-cookie header which you of course must make sure to not
cache in varnish. By default, varnish will simply not cache the whole response
when there is a set-cookie header. (Maybe you could do something more clever -
if you do, please provide a VCL example.)


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

On client side, you need some javascript code that reads out the flash messages
from the cookie and writes them into the DOM, then deletes the cookie to only
show the flash message once. This could look along these lines:

```
function getCookie(cname)
{
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name)==0) {
            return c.substring(name.length,c.length);
        }
    }

    return false;
}

function showFlash()
{
    var cookie = getCookie("flashes"); // fos_http_cache.flash_message_listener.name

    if (!cookie) {
        return;
    }

    var flashes = JSON.parse(cookie);

    // show flashes in your DOM...

    document.cookie = "flashes=; expires=Thu, 01 Jan 1970 00:00:01 GMT;";
}

// register showFlash on the page ready event.
```

Your VCL configuration should [filter out this cookie](https://www.varnish-cache.org/trac/wiki/VCLExampleRemovingSomeCookies)
on subsequent requests, in case the javascript side failed to remove it.
