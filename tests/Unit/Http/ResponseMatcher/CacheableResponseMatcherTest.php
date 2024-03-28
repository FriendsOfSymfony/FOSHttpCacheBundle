<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\Http\ResponseMatcher;

use FOS\HttpCacheBundle\Http\ResponseMatcher\CacheableResponseMatcher;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CacheableResponseMatcherTest extends TestCase
{
    public static function cacheableStatusCodeProvider(): array
    {
        return [
            [200], [203], [204], [206],
            [300], [301],
            [404], [405], [410], [414],
            [501],
        ];
    }

    #[PHPUnit\DataProvider('cacheableStatusCodeProvider')]
    public function testCacheableStatus(int $status): void
    {
        $matcher = new CacheableResponseMatcher();
        $response = new Response('', $status);

        $this->assertTrue($matcher->matches($response));
    }

    public function testNonCacheableStatus(): void
    {
        $matcher = new CacheableResponseMatcher();
        $response = new Response('', 500);

        $this->assertFalse($matcher->matches($response));
    }

    public function testCustomCacheableStatus(): void
    {
        $matcher = new CacheableResponseMatcher([400]);

        $response = new Response('', 400);
        $this->assertTrue($matcher->matches($response));

        $response = new Response('', 200);
        $this->assertTrue($matcher->matches($response));
    }
}
