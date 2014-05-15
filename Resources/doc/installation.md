Installation
============

This bundle is available on [Packagist](https://packagist.org/packages/friendsofsymfony/http-cache-bundle).
You can install it using Composer:

```bash
$ composer require friendsofsymfony/http-cache-bundle:@stable
```

Then add the bundle to your application:

```php
<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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
