<?php

declare(strict_types=1);

namespace FOS\HttpCacheBundle\Tests\Resources\TestCase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

final class StubControllerResolver implements ControllerResolverInterface
{
    /** @param callable|false $controller */
    public function __construct(private readonly object|array|false $controller)
    {
    }

    public function getController(Request $request): callable|false
    {
        return $this->controller;
    }
}
