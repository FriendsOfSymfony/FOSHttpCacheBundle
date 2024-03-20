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

use FOS\HttpCacheBundle\Configuration\InvalidateRoute;
use PHPUnit\Framework\TestCase;

/**
 * Test the InvalidateRoute attribute.
 */
class InvalidateRouteTest extends TestCase
{
    public function testExecuteNoExpression(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('InvalidateRoute param id must be string');

        new InvalidateRoute([
            'name' => 'test',
            'params' => [
                'id' => [
                    'this-is-not-expression' => 'something',
                ],
            ],
        ]);
    }
}
