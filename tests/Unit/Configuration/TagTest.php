<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\Configuration;

use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\HttpCacheBundle\Exception\InvalidTagException;
use PHPUnit\Framework\TestCase;

/**
 * Test the InvalidateRoute attribute.
 */
class TagTest extends TestCase
{
    public function testExecuteInvalidParams(): void
    {
        $this->expectException(InvalidTagException::class);
        $this->expectExceptionMessage('is invalid because it contains ,');

        new Tag([
            'tags' => ['foo, bar'],
        ]);
    }
}
