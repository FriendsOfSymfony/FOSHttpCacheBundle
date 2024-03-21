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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TagListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CacheManager&MockInterface $cacheManager;

    private SymfonyResponseTagger&MockInterface $symfonyResponseTagger;

    private TagListener $listener;

    private RuleMatcherInterface&MockInterface $mustInvalidateRule;

    private RuleMatcherInterface&MockInterface $cacheableRule;

    public function setUp(): void
    {
        $this->cacheManager = \Mockery::mock(
            CacheManager::class
        );

        $this->symfonyResponseTagger = \Mockery::mock(
            SymfonyResponseTagger::class
        );

        $this->cacheableRule = \Mockery::mock(RuleMatcherInterface::class);
        $this->mustInvalidateRule = \Mockery::mock(RuleMatcherInterface::class);

        $this->listener = new TagListener(
            $this->cacheManager,
            $this->symfonyResponseTagger,
            $this->cacheableRule,
            $this->mustInvalidateRule
        );
    }

    public function testOnKernelResponseGet(): void
    {
        $tag1 = new Tag(['value' => 'item-1']);
        $tag2 = new Tag(['value' => ['item-1', 'item-2']]);

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('_tag', [$tag1, $tag2]);

        $event = $this->getEvent($request);

        $this->cacheableRule->shouldReceive('matches')->andReturn(true);

        $this->symfonyResponseTagger
            ->shouldReceive('addTags')
            ->once()
            ->with(['item-1', 'item-1', 'item-2'])
        ;

        $this->symfonyResponseTagger
            ->shouldReceive('tagSymfonyResponse')
            ->once()
            ->with($event->getResponse())
            ->andReturn($this->symfonyResponseTagger);

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseGetMatcher(): void
    {
        $tag1 = new Tag(['value' => 'item-1']);

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('id', 2);
        $request->attributes->set('_tag', [$tag1]);

        $event = $this->getEvent($request);

        $this->cacheableRule->shouldReceive('matches')->andReturn(true);

        $this->symfonyResponseTagger
            ->shouldReceive('addTags')
            ->withArgs([['item-1', 'configured-tag', 'item-2']]);

        $this->symfonyResponseTagger
            ->shouldReceive('tagSymfonyResponse')
            ->withArgs([$event->getResponse()])
            ->andReturn($this->symfonyResponseTagger);

        /** @var RuleMatcherInterface $mockMatcher */
        $mockMatcher = \Mockery::mock(RequestMatcherInterface::class)
            ->shouldReceive('matches')->once()->with($request)->andReturn(true)
            ->getMock()
        ;
        $this->listener->addRule($mockMatcher, [
            'tags' => ['configured-tag'],
            'expressions' => ['"item-" ~ id'],
        ]);
        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseGetWithExpression(): void
    {
        $tag = new Tag(['expression' => new Expression('"item-"~id')]);

        $request = new Request();
        $request->setMethod('GET');
        $request->attributes->set('_tag', [$tag]);
        $request->attributes->set('id', '123');

        $event = $this->getEvent($request);

        $this->cacheableRule->shouldReceive('matches')->andReturn(true);

        $this->symfonyResponseTagger
            ->shouldReceive('addTags')
            ->withArgs([['item-123']]);

        $this->symfonyResponseTagger
            ->shouldReceive('tagSymfonyResponse')
            ->withArgs([$event->getResponse()])
            ->andReturn($this->symfonyResponseTagger);

        $this->listener->onKernelResponse($event);
        $this->addToAssertionCount(1); // we have mockery check if methods are called as expected
    }

    public function testOnKernelResponsePost(): void
    {
        $tag = new Tag(['value' => ['item-1', 'item-2']]);

        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('id', 2);
        $request->attributes->set('_tag', [$tag]);

        $event = $this->getEvent($request);

        $this->cacheableRule->shouldReceive('matches')->andReturn(false);
        $this->mustInvalidateRule->shouldReceive('matches')->andReturn(true);

        $this->cacheManager
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(['item-1', 'item-2']);
        $this->listener->onKernelResponse($event);

        $this->cacheManager
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(['item-1', 'item-2', 'configured-tag', 'item-2']);
        /** @var RuleMatcherInterface $mockMatcher */
        $mockMatcher = \Mockery::mock(RequestMatcherInterface::class)
            ->shouldReceive('matches')->once()->with($request)->andReturn(true)
            ->getMock()
        ;
        $this->listener->addRule($mockMatcher, [
            'tags' => ['configured-tag'],
            'expressions' => ['"item-" ~ id'],
        ]);
        $this->listener->onKernelResponse($event);
    }

    private function getEvent(Request $request, Response $response = null): ResponseEvent
    {
        return new ResponseEvent(
            \Mockery::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response ?: new Response()
        );
    }
}
