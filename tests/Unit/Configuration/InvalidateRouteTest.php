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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Test the @InvalidateRoute annotation.
 */
class InvalidateRouteTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteInvalidParams()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('InvalidateRoute params must be an array');

        new InvalidateRoute([
            'name' => 'test',
            'params' => 'foo',
        ]);
    }

    public function testExecuteNoExpression()
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
