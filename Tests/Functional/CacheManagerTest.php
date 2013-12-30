<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

use FOS\HttpCacheBundle\CacheManager;

class CacheManagerTest extends FunctionalTestCase
{
    public function testInvalidateTags()
    {
        $router = \Mockery::mock('\Symfony\Component\Routing\RouterInterface');
        $cacheManager = new CacheManager($this->varnish, $router);

        $this->assertMiss(self::getResponse('/tags.php'));
        $this->assertHit(self::getResponse('/tags.php'));

        $cacheManager->invalidateTags(array('tag1'))->flush();

        $this->assertMiss(self::getResponse('/tags.php'));
    }
} 