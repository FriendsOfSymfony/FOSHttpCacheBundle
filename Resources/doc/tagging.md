Tagged Cache Invalidation
=========================

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
* invalidate the responses by tag (e.g., invalidate all responses that are tagged
  `article-42`)

Caching Proxy Configuration
---------------------------

You need to configure your caching proxy to support cache tagging. For Varnish,
you can find an example configuration in the [Varnish chapter of the FOSHttpCache library]
(https://github.com/ddeboer/FOSHttpCache/blob/master/doc/varnish.md#tagging).

Tagging Using the Cache Manager
------------------------

You can use the [Cache Manager](cache-manager.md#tags) to manually set and
invalidate tags.

Tagging Using Annotations
-------------------------

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
