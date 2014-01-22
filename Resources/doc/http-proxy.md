HTTP proxy
==========

Use the HTTP proxy classes (currently only Varnish) for lower-level access to
invalidation functionality offered by your HTTP proxy.

* [Purge](#purge)
* [Ban](#ban)
* [Refresh](#refresh)

Purge
-----

Make sure to [configure your proxy for purging](varnish.md#purge) first.

```php
$proxy
    ->purge('/my/path')
    ->purge('http://myapp.dev/absolute/url')
    ->flush();
```

Ban
---

Make sure to [configure your proxy for banning](varnish.md#ban) first.

You can invalidate all URLs matching a regular expression by using the
`ban` method:

For instance, to ban all .png files:

```php
$varnish->banPath('.*png$')->flush();
```

To ban all HTML URLs that begin with `/articles/`:

```php
$varnish->banPath('/articles/.*', 'text/html')->flush();
```

By default, URLs will be banned on all hosts. You can override this default and
specify for which hosts you want to invalidate:

```php
$varnish->banPath('*.png$', null, 'example\.com')->flush();
```

Refresh
-------

Make sure to [configure your proxy for refreshing](varnish.md#refresh) first.

You can refresh a path or an absolute URL by calling the `refresh` method:

```php
$varnish->refresh('/my/path')
    ->refresh('http://myapp.dev/absolute/url')
    ->flush();
```
