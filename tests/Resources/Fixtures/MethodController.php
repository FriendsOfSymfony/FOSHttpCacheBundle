<?php

declare(strict_types=1);

namespace FOS\HttpCacheBundle\Tests\Resources\Fixtures;

use FOS\HttpCacheBundle\Configuration\Tag;

final class MethodController
{
    #[Tag('method-tag')]
    public function showAction(): void
    {
    }
}
