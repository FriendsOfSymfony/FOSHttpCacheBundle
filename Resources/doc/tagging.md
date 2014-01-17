Tagged Cache Invalidation
=========================

Introduction
------------

If your application has many intricate relationships between cached items,
which makes it complex to invalidate them by route, cache tagging may be
useful.

Caching tagging, or more precisely [Tagged Cache Invalidation](http://blog.kevburnsjr.com/tagged-cache-invalidation),
is a simpler version of [Linked Cache Invalidation](http://tools.ietf.org/html/draft-nottingham-linked-cache-inv-03)
(LCI).

Tagged Cache Invalidation allows you to:
* assign tags to your applications’s responses (e.g., `articles`, `article-42`)
* invalidate the responses by tag (e.g., invalidate all responses that are tagged
  `article-42`)

Configuration
-------------

See the [Varnish chapter](varnish.md#tagging) on how to configure your Varnish
proxy for tagging.

Usage
-----

### Set cache tags on responses

You can set tags manually on any response object using the [Cache Manager](cache-manager.md#tags).

You can also tag your response with the `@Tag` annotation.

**Note:** the `@Tag` annotation has a dependency on the SensioFrameworkExtraBundle,
so make sure to include that in your project:

```bash
$ composer require sensio/framework-extra-bundle
```

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

You can also use [expressions](http://symfony.com/doc/current/components/expression_language/index.html)
in tags.

**Note:** expressions have a dependency on the Symfony’s ExpressionLanguage
component, so make sure to include that in your project:

```bash
$ composer require symfony/expression-language
```

This will set tag `post-123` on the response:

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

You can invalidate cache tags manually using the [Cache Manager](cache-manager.md#tags).

You can also use annotations. Annotate your controller just like you did when
setting tags:

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
will trigger invalidation of both `posts` and `post-123` tags.