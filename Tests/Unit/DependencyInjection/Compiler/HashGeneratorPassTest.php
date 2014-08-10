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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class HashGeneratorPassTest extends \PHPUnit_Framework_TestCase
{
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
     * Nothing happens when user_context.hash_generator is not enabled
     */
    public function testConfigNoContext()
    {
        $container = $this->createContainer();
        $config = $this->getBaseConfig();
        $this->extension->load(array($config), $container);
        $this->userContextListenerPass->process($container);
        $this->assertCount(10, $container->getDefinitions());
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
        $this->extension->load(array($config), $container);
        $this->userContextListenerPass->process($container);
    }

    protected function createContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
        )));
    }

    protected function getBaseConfig()
    {
        return array(
            'proxy_client' => array(
                'varnish' => array(
                    'base_url' => 'my_hostname',
                    'servers' => array(
                        '127.0.0.1'
                    )
                )
            )
        );
    }
}
