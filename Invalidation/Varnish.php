<?php

namespace FOS\HttpCacheBundle\Invalidation;

use FOS\HttpCacheBundle\Invalidation\Method\BanInterface;
use FOS\HttpCacheBundle\Invalidation\Method\PurgeInterface;
use FOS\HttpCacheBundle\Invalidation\Method\RefreshInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Message\RequestInterface;
use Monolog\Logger;

/**
 * Varnish HTTP cache
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish implements BanInterface, PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_BAN          = 'BAN';
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';

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
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * @var array|RequestInterface[]
     */
    protected $queue;

    /**
     * Constructor
     *
     * @param array           $ips    Varnish IP addresses
     * @param string          $host   Default hostname
     * @param ClientInterface $client HTTP client (optional). If no HTTP client
     *                                is supplied, a default one will be
     *                                created automatically.
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
    public function ban($path, $contentType = self::CONTENT_TYPE_ALL, array $hosts = null)
    {
        $hosts = is_array($hosts) ? $hosts : array($this->host);
        $hostRegEx = count($hosts) > 0 ? '^('.join('|', $hosts).')$' : self::REGEX_MATCH_ALL;

        $headers = array(
            sprintf('%s: %s', self::HTTP_HEADER_HOST, $hostRegEx),
            sprintf('%s: %s', self::HTTP_HEADER_URL, $path),
            sprintf('%s: %s', self::HTTP_HEADER_CONTENT_TYPE, $contentType)
        );

        $this->queueRequest(self::HTTP_METHOD_BAN, '/', $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url)
    {
        $this->queueRequest(
            self::HTTP_METHOD_REFRESH,
            $url,
            array('Cache-Control' => 'no-cache')
        );

        return $this;
    }

    /**
     * Flush the queue
     *
     */
    public function flush()
    {
        $this->sendRequests($this->queue);
        $this->queue = array();
    }

    /**
     * Add a request to the queue
     *
     * @param string $method  HTTP method
     * @param string $url     URL
     * @param array  $headers HTTP headers
     *
     * @return RequestInterface Request that was added to the queue
     */
    protected function queueRequest($method, $url, array $headers = array())
    {
        $request = $this->client->createRequest($method, $url, $headers);

        // If $url doesn't contain a hostname, and Host header hasn't yet been
        // set, set the Host header to the default hostname
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host']) && '' != $request->getHeader('Host')
        ) {
            $request->setHeader('Host', $this->host);
        }

        $this->queue[] = $request;

        return $request;
    }

    /**
     * Sends all requests to each Varnish instance
     *
     * Requests are sent in parallel to minimise impact on performance.
     *
     * @param RequestInterface[] $requests Requests
     */
    protected function sendRequests(array $requests)
    {
        $allRequests = array();

        foreach ($requests as $request) {
            foreach ($this->ips as $ip) {
                $varnishRequest = $this->client->createRequest(
                    $request->getMethod(),
                    $ip . $request->getResource(),
                    $request->getHeaders()
                );

                $allRequests[] = $varnishRequest;
            }
        }

        try {
            $responses = $this->client->send($allRequests);
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