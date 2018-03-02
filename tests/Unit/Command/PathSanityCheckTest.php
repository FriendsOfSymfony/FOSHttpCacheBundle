<?php

/*
* This file is part of the FOSHttpCacheBundle package.
*
* (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace FOS\HttpCacheBundle\Tests\Unit\Command;

use FOS\HttpCacheBundle\Command\PathSanityCheck;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PathSanityCheckTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function pathProvider()
    {
        return [
            [false, '/foo'],
            [false, '/foo?bar=^'],
            [false, '/foo?bar=$€'],
            [false, '/foo?bar[baz]=42'],
            [true, '^/foo'],
            [true, 'foo$'],
            [true, '/foo/(bar|baz)'],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testLooksLikeRegularExpression($expected, $path)
    {
        $sanityChecking = new SanityChecking();
        $this->assertEquals($expected, $sanityChecking->looksLikeRegularExpression($path));
    }
}

class SanityChecking
{
    use PathSanityCheck {
        looksLikeRegularExpression as traitFunction;
    }

    public function looksLikeRegularExpression($path)
    {
        return $this->traitFunction($path);
    }
}
