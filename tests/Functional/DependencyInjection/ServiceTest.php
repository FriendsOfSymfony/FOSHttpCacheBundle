<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Make sure all services that are defined can actually be instantiated.
 */
class ServiceTest extends KernelTestCase
{
    /**
     * Boots a special kernel with a compiler pass to make all services public for this test.
     *
     * @return KernelInterface A KernelInterface instance
     */
    protected function bootDebugKernel()
    {
        static::ensureKernelShutdown();

        /** @var \AppKernel $kernel */
        static::$kernel = static::createKernel();
        static::$kernel->addCompilerPass(new ServicesPublicPass());
        $fs = new Filesystem();
        $fs->remove(static::$kernel->getCacheDir());
        static::$kernel->boot();

        return static::$kernel;
    }

    public function testCanBeLoaded()
    {
        /** @var Container $container */
        $container = $this->bootDebugKernel()->getContainer();
        if (!$container instanceof Container) {
            $this->markTestSkipped('Container is not of expected class but '.get_class($container));
        }

        foreach ($container->getServiceIds() as $id) {
            if (strncmp('fos_http_cache.', $id, 15)) {
                continue;
            }
            // skip deprecated service
            if ('fos_http_cache.user_context.logout_handler' === $id) {
                continue;
            }
            $this->assertInternalType('object', $container->get($id));
        }
    }
}

class ServicesPublicPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getServiceIds() as $id) {
            if (strncmp('fos_http_cache.', $id, 15)) {
                continue;
            }
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setPublic(true);
            } elseif ($container->hasAlias($id)) {
                $container->getAlias($id)->setPublic(true);
            }
        }
    }
}
