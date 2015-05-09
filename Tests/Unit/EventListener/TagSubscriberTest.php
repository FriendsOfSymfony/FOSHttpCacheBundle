<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\EventListener\TagSubscriber;
use FOS\HttpCacheBundle\Handler\TagHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TagSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TagHandler|\Mockery\Mock
     */
    protected $tagHandler;

    /**
     * @var TagSubscriber
     */
    protected $listener;

    public function setUp()
    {
        $this->tagHandler = \Mockery::mock(
            '\FOS\HttpCacheBundle\Handler\TagHandler'
        )->shouldDeferMissing();

        $this->listener = new TagSubscriber($this->tagHandler);
    }

    public function testOnKernelResponseGet()
    {
        $tag1 = new Tag(array('value' => 'item-1'));
        $tag2 = new Tag(array('value' => array('item-1', 'item-2')));

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('id', 2);
        $request->attributes->set('_tag', array($tag1, $tag2));

        $event = $this->getEvent($request);
        $this->listener->onKernelResponse($event);

        $this->assertEquals(
            'item-1,item-2',
            $event->getResponse()->headers->get($this->tagHandler->getTagsHeaderName())
        );

        $mockMatcher = \Mockery::mock('FOS\HttpCacheBundle\Http\RuleMatcherInterface')
            ->shouldReceive('matches')->once()->with($request, $event->getResponse())->andReturn(true)
            ->getMock()
        ;
        $this->listener->addRule($mockMatcher, array(
            'tags' => array('configured-tag'),
            'expressions' => array('"item-" ~ id'),
        ));
        $this->listener->onKernelResponse($event);

        $this->assertEquals(
            'item-1,item-2,configured-tag',
            $event->getResponse()->headers->get($this->tagHandler->getTagsHeaderName())
        );
    }

    public function testOnKernelResponseGetWithExpression()
    {
        $tag = new Tag(array('expression' => '"item-"~id'));

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('_tag', array($tag));
        $request->attributes->set('id', '123');

        $event = $this->getEvent($request);
        $this->listener->onKernelResponse($event);

        $this->assertEquals(
            'item-123',
            $event->getResponse()->headers->get($this->tagHandler->getTagsHeaderName())
        );
    }

    public function testOnKernelResponsePost()
    {
        $tag = new Tag(array('value' => array('item-1', 'item-2')));

        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('id', 2);
        $request->attributes->set('_tag', array($tag));

        $event = $this->getEvent($request);

        $this->tagHandler
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(array('item-1', 'item-2'));
        $this->listener->onKernelResponse($event);

        $this->tagHandler
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(array('item-1', 'item-2', 'configured-tag', 'item-2'));
        $mockMatcher = \Mockery::mock('FOS\HttpCacheBundle\Http\RuleMatcherInterface')
            ->shouldReceive('matches')->once()->with($request, $event->getResponse())->andReturn(true)
            ->getMock()
        ;
        $this->listener->addRule($mockMatcher, array(
            'tags' => array('configured-tag'),
            'expressions' => array('"item-" ~ id'),
        ));
        $this->listener->onKernelResponse($event);
    }

    protected function getEvent(Request $request, Response $response = null)
    {
        return new FilterResponseEvent(
            \Mockery::mock('\Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response ?: new Response()
        );
    }
}
