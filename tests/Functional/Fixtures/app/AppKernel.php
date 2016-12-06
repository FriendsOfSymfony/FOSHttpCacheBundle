<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use FOS\HttpCacheBundle\Tests\Functional\Fixtures\Session\TestSessionStorage;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
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
        $loader->load(__DIR__.'/config/config.yml');

        if (class_exists('Symfony\Component\Asset\Package')) {
            $loader->load(function (ContainerBuilder $container) {
                $container->loadFromExtension('framework', ['assets' => []]);
            });
        }
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
    protected function getContainerBaseClass()
    {
        return \PSS\SymfonyMockerContainer\DependencyInjection\MockerContainer::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareContainer(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        parent::prepareContainer($container);

        $container->setDefinition(
            'session.test_storage',
            new \Symfony\Component\DependencyInjection\Definition(TestSessionStorage::class)
        );
    }
}
