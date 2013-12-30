<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

use FOS\HttpCacheBundle\Invalidation\Varnish;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    private static $client;

    /**
     * @var Varnish
     */
    protected $varnish;

    const CACHE_MISS = 'MISS';
    const CACHE_HIT  = 'HIT';

    public function setUp()
    {
        $this->varnish = new Varnish(array('http://127.0.0.1:6081'), 'localhost:6081');

        // After each test, restart Varnish to clear caches
        exec('sudo service varnish restart');
    }

    public static function getClient()
    {
        if (null === self::$client) {
            self::$client = new Client('http://localhost:6081');
        }

        return self::$client;
    }

    public static function getResponse($url, array $headers = array())
    {
        return self::getClient()->get($url, $headers)->send();
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