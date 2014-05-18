Tagged Cache Invalidation
=========================

* [Introduction](#introduction)
* [Basic Configuration](#basic-configuration)
* [Tagging with the Cache Manager](#tagging-with-the-cache-manager)
* [Tagging with Configuration](#tagging-with-configuration)
* [Tagging with Annotations](#tagging-with-annotations)
  * [Expressions](#expressions)
  * [Invalidate Tags](#invalidate-tags)

Introduction
------------

If your application has many intricate relationships between cached items,
which makes it complex to invalidate them by route, cache tagging may be
useful.

Cache tagging, or more precisely [Tagged Cache Invalidation](http://blog.kevburnsjr.com/tagged-cache-invalidation),
is a simpler version of [Linked Cache Invalidation](http://tools.ietf.org/html/draft-nottingham-linked-cache-inv-03)
(LCI).

Tagged Cache Invalidation allows you to:
* assign tags to your applications’s responses (e.g., `articles`, `article-42`)
* [invalidate the responses by tag](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/cache-invalidator.md#tags) (e.g., invalidate all responses that are tagged
  `article-42`)

Basic Configuration
-------------------

You need to configure your caching proxy to support cache tagging. See the FOSHttpCache
documentation’s [Varnish Configuration chapter](https://github.com/FriendsOfSymfony/FOSHttpCache/blob/master/doc/varnish-configuration.md#tagging)
for more details.

The tag system is controlled by `fos_http_cache.tag_listener.enabled`. By
default, this setting is on `auto`, meaning tagging is activated if you have a
proxy client configured and you have `symfony/expression-language` available in
your project. If you use tagging, it is recommended to set enabled to true to
be notified if your setup is broken:

```yaml
fos_http_cache:
    tag_listener:
        enabled: true
```

Tagging with the Cache Manager
------------------------------

See the [Cache Manager chapter](cache-manager.md#tags) for more information on how
to manually set and invalidate tags.

Tagging with Configuration
--------------------------

The `rules` section of the configuration can also be used to define cache tags
on paths. You can set either or both headers and tags for each match. For the
request matching rules, see the ["match" section in "Caching Headers"](caching-headers-configuration.md#match).

When the request matches all criteria, the tags are applied. If the request was
a GET or HEAD request, the response will get the specified tags in its header.
On all other operations, those tags will be invalidated.

```yaml
fos_http_cache:
    rules:
        -
            match:
                path: ^/news
            tags: [news-section]
```

When a request goes to any URL starting with news, e.g. `/news/42`, the
response will be tagged with "news-section", in addition to any tags set by the
code or through annotations.

When a POST goes to `/news/3`, the tag "news-section" is invalidated, in
addition to any other invalidation requests done with the cache manager or
through annotations.

Tagging with Annotations
------------------------

You can make this bundle tag your response automatically using the `@Tag`
annotation. GET operations will lead to the response being tagged, modifying
operations like POST, PUT, or DELETE will lead to the tags being invalidated.

**Note:** the `@Tag` annotation has a dependency on the SensioFrameworkExtraBundle,
so if you want to use the annotation, make sure to include that in your project:
`sensio/framework-extra-bundle`.

A simple example might look like this:

```php
use FOS\HttpCacheBundle\Configuration\Tag;

class PostController extends Controller
{
    /**
     * @Tag("posts")
     */
    public function indexAction()
    {
        // ...
    }
}
```

When `indexAction()` returns a successful response for a safe (GET or HEAD)
request, the response will get the tag `posts`. The tag is set in a custom
HTTP header (`X-Cache-Tags`, by default).

Multiple tags are possible:

```php
    /**
     * @Tag("posts")
     * @Tag("posts-list")
     */
    public function indexAction()
    {
        // ...
    }
```

If you prefer, you can combine your tags in one annotation:

```php
    /**
     * @Tag({"posts", "posts-list"})
     */
```

### Expressions

You can also use [expressions](http://symfony.com/doc/current/components/expression_language/index.html)
in tags. Expressions have a dependency on Symfony’s ExpressionLanguage
component, so make sure to include that in your project:

```bash
$ composer require symfony/expression-language
```

The annotation below will set tag `post-123` on the response:

```php
    /**
     * @Tag(expression="'post-'~id")
     */
    public function showAction($id)
    {
        // Assume $id equals 123
    }
```

Or, using a [param converter](http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html):

```php
    /**
     * @Tag(expression="'post-'~post.id")
     */
    public function showAction(Post $post)
    {
        // Assume $post->getId() returns 123
    }
```

### Invalidate tags

Invalidate with annotations works just the same. Annotate your modifying
actions just like you did when setting tags:

```php
use FOS\HttpCacheBundle\Configuration\Tag;

class PostController extends Controller
{
    /**
     * @Tag(expression="'post-'~post.id")
     * @Tag("posts")
     */
    public function editAction(Post $post)
    {
        // Assume $post->getId() returns 123
    }
}
```

Any non-safe request to the `editAction` that returns a successful response
will trigger invalidation of both the `posts` and the `post-123` tags.
