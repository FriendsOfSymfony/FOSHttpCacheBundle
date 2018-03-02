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

use FOS\HttpCacheBundle\DependencyInjection\Compiler\HashGeneratorPass;
use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class HashGeneratorPassTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FOSHttpCacheExtension
     */
    private $extension;

    /**
     * @var HashGeneratorPass
     */
    private $userContextListenerPass;

    protected function setUp()
    {
        $this->extension = new FOSHttpCacheExtension();
        $this->userContextListenerPass = new HashGeneratorPass();
    }

    /**
     * Nothing happens when user_context.hash_generator is not enabled.
     */
    public function testConfigNoContext()
    {
        $container = $this->createContainer();
        $config = $this->getBaseConfig();
        $this->extension->load([$config], $container);
        $this->userContextListenerPass->process($container);
        if ($container->hasDefinition('service_container')) {
            // symfony 3.3+
            $this->assertCount(22, $container->getDefinitions());
        } else {
            $this->assertCount(21, $container->getDefinitions());
        }
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage No user context providers found
     */
    public function testConfigNoProviders()
    {
        $container = $this->createContainer();
        $config = $this->getBaseConfig();
        $config['user_context']['enabled'] = true;
        $this->extension->load([$config], $container);
        $this->userContextListenerPass->process($container);
    }

    /**
     * Test that priorities allow for us to re-arrange services.
     */
    public function testConfigPrioritisedProviders()
    {
        $container = $this->createContainer();

        $definitions = [
            'test.provider' => new Definition('\\stdClass'),
            'foo.provider' => new Definition('\\stdClass'),
            'bar.provider' => new Definition('\\stdClass'),
        ];
        $definitions['test.provider']->addTag(HashGeneratorPass::TAG_NAME, ['priority' => 10]);
        $definitions['foo.provider']->addTag(HashGeneratorPass::TAG_NAME);
        $definitions['bar.provider']->addTag(HashGeneratorPass::TAG_NAME, ['priority' => 5]);

        $container->setDefinitions($definitions);

        $config = $this->getBaseConfig();
        $config['user_context']['enabled'] = true;
        $this->extension->load([$config], $container);
        $this->userContextListenerPass->process($container);

        $arguments = $container->getDefinition('fos_http_cache.user_context.hash_generator')->getArguments();
        $services = array_map(
            function ($argument) {
                return (string) $argument;
            }, array_shift($arguments)
        );

        $this->assertEquals($services, ['test.provider', 'bar.provider', 'foo.provider']);
    }

    protected function createContainer()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
        ]));
    }

    protected function getBaseConfig()
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
        ];
    }
}
