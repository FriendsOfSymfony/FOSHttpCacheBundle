Flash Message Listener
======================

**Prerequisites**: *none*

Symfony flash messages are used to track notifications when the response to a
POST request redirects to another page. For example, after logging out, the
user is redirected to the home page and a notification "You have been logged
out" is displayed.

To achieve this, flash messages are stored in the user session. For caching, you
want to avoid sessions. And if you use the :doc:`user context <../user-context>`
feature to cache pages of logged in users, its important to not include flash
messages in the rendered pages to avoid mixing up notifications.

When the flash message listener is enabled, it moves all flash messages out of
the session into a cookie. Instead of rendering the messages in Twig, you need
to render them on client side in Javascript. The flash message cookie is sent
to the client as a ``SET-COOKIE`` header. Responses that set a cookie are never
cached. This should not be an issue, as a message typically happens after an
action was triggered on the server, and such requests must be sent as POST
(or PUT or other non-cacheable) requests.

The flash message listener is automatically enabled if you configure any of
the :doc:`options under flash_message <../../reference/configuration/flash-message>`.

.. code-block:: yaml

    # app/config.yml
    fos_http_cache:
        flash_message:
            enabled: true

On the client side, you need some JavaScript code that reads out the flash
messages from the cookie and writes them into the DOM, then deletes the cookie
to only show the flash message once. Something along these lines:

.. code-block:: javascript

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
        var cookie = getCookie("flashes"); // fos_http_cache.flash_message.name

        if (!cookie) {
            return;
        }

        var flashes = JSON.parse(decodeURIComponent(cookie));

        var html = '';
        for (var key in flashes) {
            if (key === 'length' || !flashes.hasOwnProperty(key)) {
                continue;
            }
            html = '<div class="alert alert-' + key + '">';
            html += flashes[key];
            html += '</div>';
        }
        // YOUR WORK: show flashes in your DOM...

        // remove the cookie to not show flashes again
        // path is the fos_http_cache.flash_message.path value
        document.cookie = "flashes=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/";
    }

    // YOUR WORK: register showFlash on the page ready event.

The parts about adding the flash messages in the DOM and registering your handler depend on the JavaScript framework you use in your page.

Your cache must filter cookies from requests to only keep the session cookie,
for when the redirected request is send, and in case the JavaScript failed to
remove the flash message cookie.
