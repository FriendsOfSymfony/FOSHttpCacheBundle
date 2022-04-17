<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * @var CompilerPassInterface[]
     */
    private $compilerPasses = [];

    private $serviceOverride = [];

    public function addServiceOverride(string $config): void
    {
        $this->serviceOverride[] = $config;
    }

    public function addCompilerPass(CompilerPassInterface $compilerPass): void
    {
        $this->compilerPasses[] = $compilerPass;
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        foreach ($this->compilerPasses as $compilerPass) {
            $container->addCompilerPass($compilerPass);
        }
    }

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \FOS\HttpCacheBundle\FOSHttpCacheBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if (isset($_ENV['KERNEL_CONFIG']) && $_ENV['KERNEL_CONFIG']) {
            $loader->load(__DIR__.'/config/'.$_ENV['KERNEL_CONFIG']);
        } else {
            $loader->load(__DIR__.'/config/config.yml');
        }
        if (\version_compare(Kernel::VERSION, '6.0', '>=')) {
            $loader->load(__DIR__.'/config/config_6.yml');
        } elseif (\version_compare(Kernel::VERSION, '5.0', '>=')) {
            $loader->load(__DIR__.'/config/config_50.yml');
        } elseif (\version_compare(Kernel::VERSION, '4.1', '>=')) {
            $loader->load(__DIR__.'/config/config_41.yml');
        } elseif (\version_compare(Kernel::VERSION, '4.0', '>=')) {
            $loader->load(__DIR__.'/config/config_40.yml');
        } else {
            $loader->load(__DIR__.'/config/config3.yml');
        }

        if (\version_compare(Kernel::VERSION, '4.2.0', '>=')) {
            $loader->load(__DIR__.'/config/services.yml');
        } else {
            $loader->load(__DIR__.'/config/services_34.yml');
        }
        foreach ($this->serviceOverride as $file) {
            $loader->load(__DIR__.'/config/'.$file);
        }
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/fos-http-cache-bundle/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/fos-http-cache-bundle/logs';
    }

    protected function prepareContainer(ContainerBuilder $container): void
    {
        parent::prepareContainer($container);

        $container->setDefinition(
            'session.test_storage',
            new Definition(MockFileSessionStorage::class)
        );
    }
}
