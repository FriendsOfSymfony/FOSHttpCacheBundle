<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\DependencyInjection\Compiler;

use FOS\HttpCacheBundle\DependencyInjection\Compiler\LoggerPass;
use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class LoggerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testLogger()
    {
        $extension = new FOSHttpCacheExtension();
        $loggerPass = new LoggerPass();
        $container = $this->createContainer();
        $config = $this->getConfig();
        $extension->load(array($config), $container);
        $container->setDefinition('logger', new Definition());
        $loggerPass->process($container);

        $this->assertHasCall($container, 'fos_http_cache.cache_manager', 'addSubscriber');
    }

    public function testNoLogger()
    {
        $extension = new FOSHttpCacheExtension();
        $loggerPass = new LoggerPass();
        $container = $this->createContainer();
        $config = $this->getConfig();
        $extension->load(array($config), $container);
        $loggerPass->process($container);

        $this->assertNotHasCall($container, 'fos_http_cache.cache_manager', 'addSubscriber');
    }

    private function assertHasCall(ContainerBuilder $container, $id, $method)
    {
        $this->assertTrue($container->hasDefinition($id));
        $definition = $container->getDefinition($id);
        $this->assertTrue($definition->hasMethodCall($method));
    }

    private function assertNotHasCall(ContainerBuilder $container, $id, $method)
    {
        $this->assertTrue($container->hasDefinition($id));
        $definition = $container->getDefinition($id);
        $this->assertFalse($definition->hasMethodCall($method));
    }

    private function createContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
        )));
    }

    private function getConfig()
    {
        return array(
            'proxy_client' => array(
                'varnish' => array(
                    'base_url' => 'my_hostname',
                    'servers' => array(
                        '127.0.0.1'
                    )
                )
            ),
            'tags' => array(
                'enabled' => true,
            ),
        );
    }
}
