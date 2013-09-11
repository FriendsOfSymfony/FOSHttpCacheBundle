<?php

namespace Driebit\HttpCacheBundle\Tests\EventListener;

use Driebit\HttpCacheBundle\HttpCache\Varnish;
use \Mockery;

class VarnishTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidateUrls()
    {
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) {
                        if (4 !== count($requests)) {
                            return false;
                        }

                        foreach ($requests as $request) {
                            if ('PURGE' !== $request->getMethod()) {
                                return false;
                            }

                            if ('my_hostname.dev' !== (string) $request->getHeaders()->get('host')) {
                                return false;
                            }
                        }

                        if (!in_array('/url/one', array($requests[0]->getPath(), $requests[1]->getPath()))) {
                            return false;
                        }

                        if (!in_array('127.0.0.1', array($requests[0]->getHost(), $requests[1]->getHost()))) {
                            return false;
                        }

                        if (!in_array('8080', array($requests[0]->getPort(), $requests[1]->getPort()))) {
                            return false;
                        }

                        if (!in_array('/url/two', array($requests[2]->getPath(), $requests[3]->getPath()))) {
                            return false;
                        }

                        if (!in_array('123.123.123.2', array($requests[2]->getHost(), $requests[3]->getHost()))) {
                            return false;
                        }

                        return true;
                    }
                )
            )
            ->getMock();

        $ips = array(
            'http://127.0.0.1:8080',
            'http://123.123.123.2',
        );

        $varnish = new Varnish($ips, 'my_hostname.dev', $client);

        $varnish->invalidateUrls(array('/url/one', '/url/two'));
    }
}