<?php

namespace Liip\CacheControlBundle\Helper;

/**
 * Helper to invalidate or force a refresh varnish entries
 *
 * Supports multiple varnish instances.
 *
 * For invalidation uses PURGE requests to the frontend.
 * See https://www.varnish-cache.org/docs/trunk/users-guide/purging.html
 *
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
    const PURGE_INSTRUCTION_PURGE = 'purge';
    const PURGE_INSTRUCTION_BAN = 'ban';

    const PURGE_HEADER_HOST = 'X-Purge-Host';
    const PURGE_HEADER_REGEX = 'X-Purge-Regex';
    const PURGE_HEADER_CONTENT_TYPE = 'X-Purge-Content-Type';

    private $ips;
    private $domain;
    private $port;
    private $purgeInstruction;

    private $lastRequestError;
    private $lastRequestInfo;

    /**
     * Constructor
     *
     * @param string $domain    the domain we want to purge urls from. only
     *                          domain and port are used, path is ignored
     * @param array  $ips       space separated list of varnish ips to talk to
     * @param int    $port      the port the varnishes listen on (its the same
     *                          port for all instances)
     * @param int    $purgeInstruction the purge instruction (purge in Varnish
     *                          2, ban possible since Varnish 3)
     */
    public function __construct($domain, array $ips, $port, $purgeInstruction)
    {
        $url = parse_url($domain);
        $this->domain = $url['host'];
        if (isset($url['port'])) {
            $this->domain .= ':' . $url['port'];
        }
        $this->ips  = $ips;
        $this->port = $port;
        $this->purgeInstruction = $purgeInstruction;
    }

    /**
     * Purge this path at all registered cache server.
     * See https://www.varnish-cache.org/docs/trunk/users-guide/purging.html
     *
     * @param string $path          Path to be purged, since varnish 3 this can
     *                              also be a regex to invalidate multiple
     *                              paths at once
     * @param array  $options       Options for cUrl Request
     * @param string $contentType
     * @param array  $hosts         Hosts (ex. domain.com), by default the
     *                              configured domain is used
     *
     * @return array An associative array with keys 'headers' and 'body' which
     *               holds a raw response from the server
     *
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    public function invalidatePath($path, array $options = array(), $contentType = '.*', array $hosts = array())
    {
        //Garanteed to be a purge request
        $options[CURLOPT_CUSTOMREQUEST] = 'PURGE';

        $request = array(
            'hosts'       => $hosts,
            'path'        => $path,
            'contentType' => $contentType,
        );

        return $this->sendRequestToAllVarnishes($request, $options);
    }

    /**
     * Force this path to be refreshed
     *
     * @param string $path    Path to be refreshed
     * @param array  $options Options for cUrl Request
     *
     * @return array An associative array with keys 'headers' and 'body' which
     *               holds a raw response from the server
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
     * @param array $request request hosts, path, urlRegEx and contentType
     * @param array $options Options for request
     *
     * @return array An associative array with keys 'headers', 'body', 'error'
     *               and 'errorNumber' for each configured Ip
     * @throws \RuntimeException if connection to one of the varnish servers fails. TODO: should we be more tolerant?
     */
    protected function sendRequestToAllVarnishes($request, array $options = array())
    {
        $requestResponseByIp = array();
        $isPurge = isset($options[CURLOPT_CUSTOMREQUEST]) && $options[CURLOPT_CUSTOMREQUEST] === 'PURGE';
        $hosts = isset($request['hosts']) && is_array($request['hosts']) ? join('|', $request['hosts']) : $this->domain;
        $path = isset($request['path']) ? $request['path'] : '/';
        $contentType = isset($request['contentType']) ? $request['contentType'] : '.*';
        $requestPath = $isPurge && $this->purgeInstruction === self::PURGE_INSTRUCTION_BAN ? '/' : $path;

        $curlHandler = curl_init($this->domain);

        $headers = array(
            sprintf('Host: %s', $this->domain),
        );

        if ($isPurge && $this->purgeInstruction === self::PURGE_INSTRUCTION_BAN) {
            // add headers for banning purpose
            $headers[] = sprintf('%s: %s', self::PURGE_HEADER_HOST, '^('.$hosts.')$');
            $headers[] = sprintf('%s: %s', self::PURGE_HEADER_REGEX, $path);
            $headers[] = sprintf('%s: %s', self::PURGE_HEADER_CONTENT_TYPE, $contentType);
        }

        if (isset($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER]    = array_merge($headers, $options[CURLOPT_HTTPHEADER]);
        } else {
            $options[CURLOPT_HTTPHEADER]    = $headers;
        }

        foreach ($options as $option => $value) {
            curl_setopt($curlHandler, (int) $option, $value);
        }

        //Default Options
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandler, CURLOPT_HEADER, true); // Display headers

        foreach ($this->ips as $ip) {

            curl_setopt($curlHandler, CURLOPT_URL, $ip.':'.$this->port.$requestPath);

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
