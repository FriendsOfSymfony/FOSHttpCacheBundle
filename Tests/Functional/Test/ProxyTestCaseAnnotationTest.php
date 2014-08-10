<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Test;

use FOS\HttpCacheBundle\Test\ProxyTestCase;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\WebTestCase;

class ProxyTestCaseAnnotationTest extends ProxyTestCase
{
    protected function setUp()
    {
        static::getContainer()->mock(
            'fos_http_cache.test.default_proxy_server',
            '\FOS\HttpCache\Test\Proxy\VarnishProxy'
        )
            ->shouldReceive('clear')->once()
        ;

        parent::setUp();
    }

    /**
     * @clearCache
     */
    public function testClearCacheAnnotation()
    {
    }
}
