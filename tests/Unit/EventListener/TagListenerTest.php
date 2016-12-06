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

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\EventListener\TagListener;
use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TagListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheManager|\Mockery\Mock
     */
    private $cacheManager;

    /**
     * @var SymfonyResponseTagger|\Mockery\Mock
     */
    private $symfonyResponseTagger;

    /**
     * @var TagListener
     */
    private $listener;

    public function setUp()
    {
        $this->cacheManager = \Mockery::mock(
            CacheManager::class
        );

        $this->symfonyResponseTagger = \Mockery::mock(
            SymfonyResponseTagger::class
        );

        $this->listener = new TagListener($this->cacheManager, $this->symfonyResponseTagger);
    }

    public function testOnKernelResponseGet()
    {
        $tag1 = new Tag(array('value' => 'item-1'));
        $tag2 = new Tag(array('value' => array('item-1', 'item-2')));

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('_tag', array($tag1, $tag2));

        $event = $this->getEvent($request);

        $this->symfonyResponseTagger
            ->shouldReceive('addTags')
            ->withArgs([['item-1', 'item-1', 'item-2']])
        ;

        $this->symfonyResponseTagger
            ->shouldReceive('tagSymfonyResponse')
            ->withArgs([$event->getResponse()])
            ->andReturn($this->symfonyResponseTagger);

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseGetMatcher()
    {
        $tag1 = new Tag(array('value' => 'item-1'));

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('id', 2);
        $request->attributes->set('_tag', array($tag1));

        $event = $this->getEvent($request);

        $this->symfonyResponseTagger
            ->shouldReceive('addTags')
            ->withArgs([['item-1', 'configured-tag', 'item-2']]);

        $this->symfonyResponseTagger
            ->shouldReceive('tagSymfonyResponse')
            ->withArgs([$event->getResponse()])
            ->andReturn($this->symfonyResponseTagger);

        /** @var RuleMatcherInterface $mockMatcher */
        $mockMatcher = \Mockery::mock(RuleMatcherInterface::class)
            ->shouldReceive('matches')->once()->with($request, $event->getResponse())->andReturn(true)
            ->getMock()
        ;
        $this->listener->addRule($mockMatcher, array(
            'tags' => array('configured-tag'),
            'expressions' => array('"item-" ~ id'),
        ));
        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseGetWithExpression()
    {
        $tag = new Tag(array('expression' => '"item-"~id'));

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('_tag', array($tag));
        $request->attributes->set('id', '123');

        $event = $this->getEvent($request);
        $this->symfonyResponseTagger
            ->shouldReceive('addTags')
            ->withArgs([['item-123']]);

        $this->symfonyResponseTagger
            ->shouldReceive('tagSymfonyResponse')
            ->withArgs([$event->getResponse()])
            ->andReturn($this->symfonyResponseTagger);

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponsePost()
    {
        $tag = new Tag(array('value' => array('item-1', 'item-2')));

        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('id', 2);
        $request->attributes->set('_tag', array($tag));

        $event = $this->getEvent($request);

        $this->cacheManager
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(array('item-1', 'item-2'));
        $this->listener->onKernelResponse($event);

        $this->cacheManager
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(array('item-1', 'item-2', 'configured-tag', 'item-2'));
        /** @var RuleMatcherInterface $mockMatcher */
        $mockMatcher = \Mockery::mock(RuleMatcherInterface::class)
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
