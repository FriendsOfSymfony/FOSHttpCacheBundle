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

use FOS\HttpCacheBundle\DependencyInjection\Compiler\TagSubscriberPass;
use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class TagSubscriberPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage requires SensioFrameworkExtraBundle
     */
    public function testNoFrameworkBundle()
    {
        $extension = new FOSHttpCacheExtension();
        $tagListenerPass = new TagSubscriberPass();
        $container = $this->createContainer();
        $config = $this->getConfig();
        $extension->load(array($config), $container);
        $tagListenerPass->process($container);
    }

    protected function createContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
        )));
    }

    protected function getConfig()
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
