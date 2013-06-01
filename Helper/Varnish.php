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
    const PURGE_INSTRUCTION_BAN   = 'ban';

    const PURGE_HEADER_HOST         = 'X-Purge-Host';
    const PURGE_HEADER_REGEX        = 'X-Purge-Regex';
    const PURGE_HEADER_CONTENT_TYPE = 'X-Purge-Content-Type';

    const CONTENT_TYPE_ALL   = '.*';
    const CONTENT_TYPE_HTML  = 'text/html';
    const CONTENT_TYPE_CSS   = 'text/css';
    const CONTENT_TYPE_JS    = 'javascript';
    const CONTENT_TYPE_IMAGE = 'image/';

    private $ips;
    private $host;
    private $port;
    private $purgeInstruction;

    private $lastRequestError;
    private $lastRequestInfo;

    /**
     * Constructor
     *
     * @param string $host      The default host we want to purge urls from.
     *                          only host and port are used, path is ignored
     * @param array  $ips       space separated list of varnish ips to talk to
     * @param int    $port      the port the varnishes listen on (its the same
     *                          port for all instances)
     * @param string $purgeInstruction the purge instruction (purge in Varnish
     *                          2, ban possible since Varnish 3)
     */
    public function __construct($host, array $ips, $port, $purgeInstruction = self::PURGE_INSTRUCTION_PURGE)
    {
        $url = parse_url($host);
        $this->host = $url['host'];
        if (isset($url['port'])) {
            $this->host .= ':' . $url['port'];
        }
        $this->ips  = $ips;
        $this->port = $port;
        $this->purgeInstruction = $purgeInstruction;
    }

    /**
     * Purge this path at all registered cache server.
     * See https://www.varnish-cache.org/docs/trunk/users-guide/purging.html
     *
     * @param string $path        Path to be purged, since varnish 3 this can
     *                            also be a regex for banning
     * @param array  $options     Options for cUrl Request
     * @param string $contentType Banning option: invalidate all or fe. only html
     * @param array  $hosts       Banning option: hosts to ban, leave null to
     *                            use default host and an empty array to ban
     *                            all hosts
     *
     * @return array An associative array with keys 'headers' and 'body' which
     *               holds a raw response from the server
     *
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    public function invalidatePath($path, array $options = array(), $contentType = self::CONTENT_TYPE_ALL, array $hosts = null)
    {
        if ($this->purgeInstruction === self::PURGE_INSTRUCTION_BAN) {
            return $this->requestBan($path, $contentType, $hosts, $options);
        } else {
            return $this->requestPurge($path, $options);
        }
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

        $options[CURLOPT_CUSTOMREQUEST] = 'GET';

        return $this->sendRequestToAllVarnishes($path, $headers, $options);
    }

    /**
     * Do a request using the purge instruction
     *
     * @param string $path    Path to be purged
     * @param array  $options Options for cUrl Request
     *
     * @return array An associative array with keys 'headers' and 'body' which
     *               holds a raw response from the server
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    protected function requestPurge($path, array $options = array())
    {
        $headers = array(
            sprintf('Host: %s', $this->host),
        );

        //Garanteed to be a purge request
        $options[CURLOPT_CUSTOMREQUEST] = 'PURGE';

        return $this->sendRequestToAllVarnishes($path, $headers, $options);
    }

    /**
     * Do a request using the ban instruction (available since varnish 3)
     *
     * @param string $path        Path to be purged, this can also be a regex
     * @param string $contentType Invalidate all or fe. only html
     * @param array  $hosts       Hosts to ban, leave null to use default host
     *                            and an empty array to ban all hosts
     * @param array  $options     Options for cUrl Request
     *
     * @return array An associative array with keys 'headers' and 'body' which
     *               holds a raw response from the server
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    protected function requestBan($path, $contentType = self::CONTENT_TYPE_ALL, array $hosts = null, array $options = array())
    {
        $hosts = is_null($hosts) ? array($this->host) : $hosts;
        $hostRegEx = count($hosts) > 0 ? '^('.join('|', $hosts).')$' : '.*';

        $headers = array(
            sprintf('%s: %s', self::PURGE_HEADER_HOST, $hostRegEx),
            sprintf('%s: %s', self::PURGE_HEADER_REGEX, $path),
            sprintf('%s: %s', self::PURGE_HEADER_CONTENT_TYPE, $contentType),
        );

        //Garanteed to be a purge request
        $options[CURLOPT_CUSTOMREQUEST] = 'PURGE';

        return $this->sendRequestToAllVarnishes('/', $headers, $options);
    }

    /**
     * Send a request to all configured varnishes
     *
     * @param string $path    URL path for request
     * @param array  $headers Headers for cUrl Request
     * @param array  $options Options for cUrl Request
     *
     * @return array An associative array with keys 'headers', 'body', 'error'
     *               and 'errorNumber' for each configured Ip
     * @throws \RuntimeException if connection to one of the varnish servers fails. TODO: should we be more tolerant?
     */
    protected function sendRequestToAllVarnishes($path, array $headers = array(), array $options = array())
    {
        $requestResponseByIp = array();
        $curlHandler = curl_init();

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

            curl_setopt($curlHandler, CURLOPT_URL, $ip.':'.$this->port.$path);

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
