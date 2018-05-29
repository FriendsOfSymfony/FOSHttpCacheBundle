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

    public function addCompilerPass(CompilerPassInterface $compilerPass)
    {
        $this->compilerPasses[] = $compilerPass;
    }

    protected function build(ContainerBuilder $container)
    {
        parent::build($container);
        foreach ($this->compilerPasses as $compilerPass) {
            $container->addCompilerPass($compilerPass);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
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

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (Kernel::MAJOR_VERSION >= 4) {
            if (Kernel::MINOR_VERSION >= 1) {
                $loader->load(__DIR__.'/config/config_41.yml');
            } else {
                $loader->load(__DIR__.'/config/config_40.yml');
            }
        } else {
            $loader->load(__DIR__.'/config/config3.yml');
        }
        $loader->load(__DIR__.'/config/services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/fos-http-cache-bundle/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/fos-http-cache-bundle/logs';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareContainer(ContainerBuilder $container)
    {
        parent::prepareContainer($container);

        $container->setDefinition(
            'session.test_storage',
            new Definition(MockFileSessionStorage::class)
        );
    }
}
