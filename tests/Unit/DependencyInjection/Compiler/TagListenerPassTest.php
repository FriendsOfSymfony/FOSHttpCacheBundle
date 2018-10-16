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

use FOS\HttpCacheBundle\DependencyInjection\Compiler\TagListenerPass;
use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class TagListenerPassTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage require the SensioFrameworkExtraBundle
     */
    public function testNoFrameworkBundle()
    {
        $extension = new FOSHttpCacheExtension();
        $tagListenerPass = new TagListenerPass();
        $container = $this->createContainer();
        $config = $this->getConfig();
        $extension->load([$config], $container);
        $tagListenerPass->process($container);
    }

    protected function createContainer()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
        ]));
    }

    protected function getConfig()
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
