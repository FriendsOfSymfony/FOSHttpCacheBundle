<?php

namespace Driebit\HttpCacheBundle\HttpCache;

use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Monolog\Logger;

/**
 * Varnish HTTP cache
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish implements HttpCacheInterface
{
    const HTTP_METHOD_PURGE = 'PURGE';

    /**
     * IP addresses of all Varnish instances
     *
     * @var array
     */
    protected $ips;

    /**
     * The hostname
     *
     * @var string
     */
    protected $host;

    /**
     * HTTP client
     *
     * @var\Guzzle\Http\ClientInterface
     */
    protected $client;

    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param array           $ips    Varnish IP addresses
     * @param string          $host   Application hostname
     * @param ClientInterface $client HTTP client
     */
    public function __construct(array $ips, $host, ClientInterface $client = null)
    {
        $this->ips = $ips;
        $this->host = $host;
        $this->client = $client ?: new Client();
    }

    /**
     * Set a logger to enable logging
     *
     * @param \Monolog\Logger $logger
     */
    public function setLogger(Logger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateUrl($url)
    {
        $this->sendRequests(self::HTTP_METHOD_PURGE, array($url));
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateUrls(array $urls)
    {
        $this->sendRequests(self::HTTP_METHOD_PURGE, $urls);
    }

    /**
     * Sends requests for each URL to each Varnish instance
     *
     * Requests are sent in parallel to minimise impact on performance.
     *
     * @param string $method HTTP method
     * @param array  $urls   URLs
     */
    protected function sendRequests($method, array $urls)
    {
        $requests = array();

        foreach ($urls as $url) {
            foreach ($this->ips as $ip) {
                $request = $this->client->createRequest($method, $ip . $url);
                $request->setHeader('Host', $this->host);
                $requests[] = $request;
            }
        }

        try {
            $responses = $this->client->send($requests);
        } catch (MultiTransferException $e) {
            /*
             * @todo what if there is no cache server available (405 'Method not allowed')
             */
            foreach ($e as $ea) {
                if ($ea instanceof CurlException) {
                    // Usually 'couldn't connect to host', which means: Varnish is down
                    $level = 'crit';
                } else {
                    $level = 'info';
                }

                $this->log(
                    sprintf(
                        'Caught exception while trying to %s %s' . PHP_EOL . 'Message: %s',
                        $ea->getRequest()->getMethod(),
                        $ea->getRequest()->getUrl(),
                        $ea->getMessage()
                    ),
                    $level
                );
            }
        }
    }

    protected function log($message, $level = 'debug')
    {
        if (null !== $this->logger) {
            $this->logger->$level($message);
        }
    }
}