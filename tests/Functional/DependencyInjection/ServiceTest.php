<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Make sure all services that are defined can actually be instantiated.
 */
class ServiceTest extends KernelTestCase
{
    public function testCanBeLoaded()
    {
        self::bootKernel();

        /** @var Container $container */
        $container = static::$kernel->getContainer();
        if (!$container instanceof Container) {
            $this->markTestSkipped('Container is not of expected class but '.get_class($container));
        }

        foreach ($container->getServiceIds() as $id) {
            if (strncmp('fos_http_cache.', $id, 15)) {
                continue;
            }
            // skip private services - hopefully getServiceIds will not return those in 4.0
            if (in_array($id, [
                'fos_http_cache.response_matcher.cacheable',
                'fos_http_cache.rule_matcher.must_invalidate',
                'fos_http_cache.request_matcher.64a9a494836a11b31a578a55f11b9565055870c5bb354ed2b1b25484ff97e5930acb1d84',
            ])) {
                continue;
            }
            $this->assertInternalType('object', $container->get($id));
        }
    }
}
