<?php

namespace Liip\CacheControlBundle\Helper;

/**
 * Helper to invalidate or force a refresh varnish entries
 *
 * Supports multiple varnish instances.
 *
 * For invalidation uses PURGE requests to the frontend.
 * See http://www.varnish-cache.org/trac/wiki/VCLExamplePurging
 *
 * This is about equivalent to doing this
 *
 *   netcat localhost 6081 << EOF
 *   PURGE /url/to/purge HTTP/1.1
 *   Host: webapp-host.name
 *
 *   EOF
 *
 * For a forced refresh it uses a normal GET with appropriate cache headers
 * See: http://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh
 *
 * This is about equivalent to doing this
 *
 *   netcat localhost 6081 << EOF
 *   GET /url/to/refresh HTTP/1.1
 *   Host: webapp-host.name
 *   Cache-Control: no-cache, no-store, max-age=0, must-revalidate
 *
 *   EOF
 *
 * TODO: would be nice to support the varnish admin shell as well. It would be
 * more clean and secure, but you have to configure varnish accordingly. By default
 * the admin port is only open for local host for security reasons.
 */
class Varnish
{
    private $ips;
    private $domain;
    private $port;

    private $lastRequestError;
    private $lastRequestInfo;

    /**
     * Constructor
     *
     * @param string $domain the domain we want to purge urls from. only domain and port are used, path is ignored
     * @param array  $ips    space separated list of varnish ips to talk to
     * @param int    $port   the port the varnishes listen on (its the same port for all instances)
     */
    public function __construct($domain, array $ips, $port)
    {
        $url = parse_url($domain);
        $this->domain = $url['host'];
        if (isset($url['port'])) {
            $this->domain .= ':' . $url['port'];
        }
        $this->ips  = $ips;
        $this->port = $port;

    }

    /**
     * Purge this absolute path at all registered cache server
     *
     * @param string $path    Must be an absolute path
     * @param array  $options Options for cUrl Request
     *
     * @return array An associative array with keys 'headers' and 'body' which holds a raw response from the server
     *
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    public function invalidatePath($path, array $options = array())
    {
        //Garanteed to be a purge request
        $options[CURLOPT_CUSTOMREQUEST] = 'PURGE';

        $request = array('path' => $path);

        return $this->sendRequestToAllVarnishes($request, $options);
    }

    /**
     * Force this absolute path to be refreshed
     *
     * @param string $path    Must be an absolute path
     * @param array  $options Options for cUrl Request
     *
     * @return array             An associative array with keys 'headers' and 'body' which holds a raw response from the server
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    public function refreshPath($path, array $options = array())
    {

        $headers = array("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");

        $options[CURLOPT_HTTPHEADER]    = $headers;
        $options[CURLOPT_CUSTOMREQUEST] = 'GET';

        $request = array('path' => $path);

        return $this->sendRequestToAllVarnishes($request, $options);
    }

    /**
     * Send a request to all configured varnishes
     *
     * @param array $request request string
     * @param array $options Options for request
     *
     * @return array             An associative array with keys 'headers', 'body', 'error' and 'errorNumber' for each configured Ip
     * @throws \RuntimeException if connection to one of the varnish servers fails. TODO: should we be more tolerant?
     */
    protected function sendRequestToAllVarnishes($request, array $options = array())
    {

        $requestResponseByIp = array();

        $curlHandler = curl_init($this->domain);

        foreach ($options as $option => $value) {

            curl_setopt($curlHandler, (int) $option, $value);
        }
        //Default Options
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandler, CURLOPT_HEADER, true); // Display headers

        foreach ($this->ips as $ip) {

            curl_setopt($curlHandler, CURLOPT_URL, $ip.':'.$this->port.$request['path']);

            $response = curl_exec($curlHandler);

            //Failed
            if ($response === false) {
                $header = '';
                $body   = '';
                $error  = curl_error($curlHandler);
                $errorNumber = curl_errno($curlHandler);

            } else {
                $error = null;
                $errorNumber = CURLE_OK;
                list($header, $body) = explode("\r\n\r\n", $response, 2);
            }

            $requestResponseByIp[$ip] = array('headers' => $header,
                                              'body'    => $body,
                                              'error'   => $error,
                                              'errorNumber' => $errorNumber);

        }

        curl_close($curlHandler);

        return $requestResponseByIp;
    }

}
