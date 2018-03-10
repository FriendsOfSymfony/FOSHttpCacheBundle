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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Test the @InvalidateRoute annotation.
 */
class TagTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @expectedException \FOS\HttpCacheBundle\Exception\InvalidTagException
     * @expectedExceptionMessage is invalid because it contains ,
     */
    public function testExecuteInvalidParams()
    {
        new Tag([
            'tags' => ['foo, bar'],
        ]);
    }
}
