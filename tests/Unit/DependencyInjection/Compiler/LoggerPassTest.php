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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class LoggerPassTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testLogger()
    {
        $extension = new FOSHttpCacheExtension();
        $loggerPass = new LoggerPass();
        $container = $this->createContainer();
        $config = $this->getConfig();
        $extension->load([$config], $container);
        $container->setDefinition('logger', new Definition());
        $loggerPass->process($container);

        $this->assertHasTaggedService($container, 'fos_http_cache.event_listener.log', 'kernel.event_subscriber');
    }

    public function testNoLogger()
    {
        $extension = new FOSHttpCacheExtension();
        $loggerPass = new LoggerPass();
        $container = $this->createContainer();
        $config = $this->getConfig();
        $extension->load([$config], $container);
        $loggerPass->process($container);

        $this->assertIsAbstract($container, 'fos_http_cache.event_listener.log');
    }

    private function assertHasTaggedService(ContainerBuilder $container, $id, $tag)
    {
        $this->assertTrue($container->hasDefinition($id));
        $definition = $container->getDefinition($id);
        $this->assertFalse($definition->isAbstract());
        $this->assertTrue($definition->hasTag($tag));
    }

    private function assertIsAbstract(ContainerBuilder $container, $id)
    {
        $this->assertTrue($container->hasDefinition($id));
        $definition = $container->getDefinition($id);
        $this->assertTrue($definition->isAbstract());
    }

    private function createContainer()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
        ]));
    }

    private function getConfig()
    {
        return [
            'proxy_client' => [
                'varnish' => [
                    'http' => [
                        'base_url' => 'my_hostname',
                        'servers' => [
                            '127.0.0.1',
                        ],
                    ],
                ],
            ],
            'tags' => [
                'enabled' => true,
            ],
        ];
    }
}
