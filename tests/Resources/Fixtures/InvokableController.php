<?php

declare(strict_types=1);

namespace FOS\HttpCacheBundle\Tests\Resources\Fixtures;

use FOS\HttpCacheBundle\Configuration\Tag;

final class InvokableController
{
    #[Tag('invoke-tag')]
    public function __invoke(): void
    {
    }
}
