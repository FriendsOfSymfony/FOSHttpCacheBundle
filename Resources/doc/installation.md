Installation
============

Installation
------------

This library is available on [Packagist](https://packagist.org/packages/friendsofsymfony/http-cache-bundle).
You can install it using Composer:

```bash
$ composer require friendsofsymfony/http-cache-bundle:@stable
```

Then add the bundle to your application:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new FOS\HttpCacheBundle\FOSHttpCacheBundle(),
        // ...
    );
}
```