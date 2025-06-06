<?php

declare(strict_types=1);

namespace FOS\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\EventListener\AttributesListener;
use FOS\HttpCacheBundle\Tests\Resources\Fixtures\InvokableController;
use FOS\HttpCacheBundle\Tests\Resources\Fixtures\MethodController;
use FOS\HttpCacheBundle\Tests\Resources\TestCase\StubControllerResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AttributesListenerTest extends TestCase
{
    /** @param string[] $expectedTags */
    #[DataProvider('supportedControllersProvider')]
    public function testSupportedControllers(callable|false $controller, array $expectedTags): void
    {
        $controllerResolver = new StubControllerResolver($controller);
        $listener = new AttributesListener($controllerResolver);
        $request = Request::create('/');
        $requestEvent = new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->onKernelRequest($requestEvent);

        $this->assertRequestTags($request, $expectedTags);
    }

    /** @return iterable<string, array{callable|false, string[]}> */
    public static function supportedControllersProvider(): iterable
    {
        yield 'false' => [
            false,
            [],
        ];

        yield 'invoke' => [
            new InvokableController(),
            ['invoke-tag'],
        ];

        yield 'methode' => [
            [new MethodController(), 'showAction'],
            ['method-tag'],
        ];
    }

    /** @param string[] $expectedTags */
    private function assertRequestTags(Request $request, array $expectedTags): void
    {
        $this->assertSame(
            $expectedTags,
            \array_map(
                static fn (Tag $tag): string => \implode($tag->getTags()),
                $request->attributes->get('_tag', []),
            ),
        );
    }
}
