<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    private static $client;

    const CACHE_MISS = 'MISS';
    const CACHE_HIT  = 'HIT';

    public static function getClient()
    {
        if (null === self::$client) {
            self::$client = new Client('http://localhost:6081');
        }

        return self::$client;
    }

    public static function getResponse($url)
    {
        return self::getClient()->get($url);
    }

    public function assertMiss(Response $response, $message = null)
    {
        $this->assertEquals(self::CACHE_MISS, (string) $response->getHeader('X-Cache'), $message);
    }

    public function assertHit(Response $response, $message = null)
    {
        $this->assertEquals(self::CACHE_HIT, (string) $response->getHeader('X-Cache'), $message);
    }
} 