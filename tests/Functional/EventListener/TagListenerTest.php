<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\EventListener\TagListener;
use FOS\HttpCacheBundle\EventListener\TagResponseEvent;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class TagListenerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;
    private static $overrideService = false;

    /**
     * @requires PHP 8.0
     */
    public function testAttributeTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/php8/tag/list');
        $response = $client->getResponse();
        $this->assertEquals('all-items,item-123', $response->headers->get('X-Cache-Tags'));

        $client->request('GET', '/php8/tag/123');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('item-123', $response->headers->get('X-Cache-Tags'));
    }

    public function testErrorIsNotInvalidated()
    {
        self::$overrideService = true;
        $client = static::createClient();

        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('flush')
            ->twice()
            ->andReturn(0)
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $client->request('POST', '/php8/tag/error');
    }

    public function testConfigurationTagsAreSet()
    {
        $client = static::createClient();

        $client->request('GET', '/cached/51');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('area,area-51', $response->headers->get('X-Cache-Tags'));
    }

    public function testManualTagging()
    {
        $client = static::createClient();

        $client->request('GET', '/php8/tag_manual');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('manual-tag,sub-tag,sub-items,manual-items', $response->headers->get('X-Cache-Tags'));
    }

    public function testTwigExtension()
    {
        $client = static::createClient();

        $client->request('GET', '/php8/tag_twig');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('tag-from-twig', $response->headers->get('X-Cache-Tags'));
    }

    /**
     * @dataProvider cacheableRequestResponseCombinations
     */
    public function testTagsAreSetWhenCacheable(Request $request, Response $response)
    {
        self::$overrideService = true;
        $request->attributes->set('_tag', [new Tag(['value' => ['cacheable-is-tagged']])]);
        $client = static::createClient();

        $event = new TagResponseEvent(
            $client->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        // No invalidation
        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        /** @var TagListener $tagListener */
        $tagListener = $client->getContainer()->get('fos_http_cache.event_listener.tag');
        $tagListener->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertEquals('cacheable-is-tagged', $headers->get('X-Cache-Tags'));
    }

    /**
     * @dataProvider mustInvalidateRequestResponseCombinations
     */
    public function testTagsAreInvalidated(Request $request, Response $response)
    {
        self::$overrideService = true;
        $request->attributes->set('_tag', [new Tag(['value' => ['invalidated']])]);
        $client = static::createClient();

        $event = new TagResponseEvent(
            $client->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $mock->shouldReceive('invalidateTags')
            ->once()
            ->with(['invalidated'])
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        /** @var TagListener $tagListener */
        $tagListener = $client->getContainer()->get('fos_http_cache.event_listener.tag');
        $tagListener->onKernelResponse($event);

        $headers = $event->getResponse()->headers;

        // No cache tags set
        $this->assertFalse($headers->has('X-Cache-Tags'));
    }

    /**
     * @dataProvider mustNotInvalidateRequestResponseCombinations
     */
    public function testTagsAreNotInvalidated(Request $request, Response $response)
    {
        self::$overrideService = true;
        $request->attributes->set('_tag', [new Tag(['value' => ['not-invalidated']])]);
        $client = static::createClient();

        $event = new TagResponseEvent(
            $client->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        // No invalidation
        $mock = \Mockery::mock(CacheManager::class);
        $mock->shouldReceive('supports')
            ->zeroOrMoreTimes()
            ->andReturnTrue()
        ;
        $client->getContainer()->set('fos_http_cache.cache_manager', $mock);

        $tagListener = $client->getContainer()->get('fos_http_cache.event_listener.tag');
        $tagListener->onKernelResponse($event);

        $headers = $event->getResponse()->headers;

        // No cache tags set
        $this->assertFalse($headers->has('X-Cache-Tags'));
    }

    public function cacheableRequestResponseCombinations()
    {
        return [
            [Request::create('', 'GET'), new Response('', 200)],
            [Request::create('', 'HEAD'), new Response('', 200)],
            // https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/issues/286
            [Request::create('', 'GET'), new Response('', 301)],
        ];
    }

    public function mustInvalidateRequestResponseCombinations()
    {
        return [
            // https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/issues/241
            [Request::create('', 'POST'), new Response('', 201)],
        ];
    }

    public function mustNotInvalidateRequestResponseCombinations()
    {
        return [
            // https://github.com/FriendsOfSymfony/FOSHttpCacheBundle/issues/279
            [Request::create('', 'OPTIONS'), new Response('', 200)],
        ];
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);
        \assert($kernel instanceof \AppKernel);
        if (static::$overrideService) {
            $kernel->addServiceOverride('override_cache_manager.yml');
        }

        return $kernel;
    }
}
