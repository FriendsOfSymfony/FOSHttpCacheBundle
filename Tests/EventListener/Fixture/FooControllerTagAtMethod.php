<?php

namespace FOS\HttpCacheBundle\Tests\EventListener\Fixture;

use FOS\HttpCacheBundle\Configuration\Tag;

class FooControllerTagAtMethod
{
    /**
     * @Tag({"article-1", "article-3"})
     * @Tag("article-2s")
     */
    public function barAction()
    {
    }

    public function expressionAction()
    {

    }
} 