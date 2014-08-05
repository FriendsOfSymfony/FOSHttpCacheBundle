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

/**
 * Test the @InvalidateRoute annotation.
 */
class InvalidateRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage InvalidateRoute params must be an array
     */
    public function testExecuteInvalidParams()
    {
        new InvalidateRoute(array(
            'name' => 'test',
            'params' => 'foo',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage InvalidateRoute param id must be string
     */
    public function testExecuteNoExpression()
    {
        new InvalidateRoute(array(
            'name' => 'test',
            'params' => array(
                'id' => array(
                    'this-is-not-expression' => 'something',
                )
            ),
        ));
    }
}
