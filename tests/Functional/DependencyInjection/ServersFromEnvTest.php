<?php

namespace FOS\HttpCacheBundle\Tests\Functional\DependencyInjection;

use FOS\HttpCache\ProxyClient\HttpDispatcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class ServersFromEnvTest extends KernelTestCase
{
    /**
     * Boots a special kernel with a compiler pass to make all services public for this test.
     *
     * @return KernelInterface A KernelInterface instance
     */
    protected function bootDebugKernel()
    {
        static::ensureKernelShutdown();
        static::$kernel = static::createKernel();
        assert(static::$kernel instanceof \AppKernel);
        static::$kernel->addCompilerPass(new ServicesPublicPass());
        $fs = new Filesystem();
        $fs->remove(static::$kernel->getCacheDir());
        static::$kernel->boot();

        return static::$kernel;
    }

    public function testServersFromEnv()
    {
        // define the kernel config to use for this test
        $_ENV['KERNEL_CONFIG'] = 'config_servers_from_env.yml';

        // test env var as json string, that will get deserialized and injected into http dispatcher
        $_ENV['VARNISH_SERVERS'] = '["localhost:123","https://any.host:456"]';

        /** @var Container $container */
        $container = $this->bootDebugKernel()->getContainer();

        /** @var HttpDispatcher $fosHttpCache */
        $fosHttpCache = $container->get('fos_http_cache.proxy_client.varnish.http_dispatcher');

        $reflectionObject = new \ReflectionClass($fosHttpCache);
        $reflectionGetServers = $reflectionObject->getMethod('getServers');
        $reflectionGetServers->setAccessible(true);
        $uris = $reflectionGetServers->invoke($fosHttpCache);
        $servers = array_map(function ($uri) { return $uri->__toString(); }, $uris);

        static::assertEquals(['http://localhost:123', 'https://any.host:456'], $servers);

        // unset env vars, so next tests do not fail (KERNEL_CONFIG)
        unset($_ENV['KERNEL_CONFIG'], $_ENV['VARNISH_SERVERS']);
    }
}
