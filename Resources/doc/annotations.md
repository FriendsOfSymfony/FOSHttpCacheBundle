Annotations
===========

You can annotate your controller actions to configure routes and paths that
should be invalidated when that action is executed.

* [Requirements](#requirements)
* [@InvalidatePath](#invalidatepath)
* [@InvalidateRoute](#invalidateroute)
* [@Tag](#tag)

Requirements
------------

This bundle’s annotations have a dependency on the SensioFrameworkExtraBundle,
so make sure to include that in your project:

```bash
$ composer require sensio/framework-extra-bundle
```

If you wish to use [expressions](http://symfony.com/doc/current/components/expression_language/index.html)
in your annotations, you also need Symfony’s ExpressionLanguage component. If
you’re not using full-stack Symfony 2.4 or later, you need to explicitly add
the component:

```bash
$ composer require symfony/expression-language
```

@InvalidatePath
---------------

```php
use FOS\HttpCacheBundle\Configuration\InvalidatePath;

    /**
     * @InvalidatePath("/posts")
     * @InvalidatePath("/posts/latest")
     */
    public function editAction()
    {
    }
}
```

@InvalidateRoute
---------------

```php
use FOS\HttpCacheBundle\Configuration\InvalidateRoute;

    /**
     * @InvalidateRoute("posts")
     * @InvalidateRoute("posts", params={"type" = "latest"})
     */
    public function editAction()
    {
    }
```

You can also use [expressions](http://symfony.com/doc/current/components/expression_language/index.html)
in the route parameter values:

```php
    /**
     * @InvalidateRoute("posts", params={"number" = "id"})
     */
    public function editAction(Request $request)
    {
        // Assume $request->attributes->get('id') returns 123
    }
```

Route `posts` will now be invalidated with value `123` for param `number`.

@Tag
----

See the [Tagging chapter](tagging.md) for more information about the `@Tag`
annotation.