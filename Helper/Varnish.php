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

    /**
     * Constructor
     *
     * @param string $domain the domain we want to purge urls from. only domain and port are used, path is ignored
     * @param array $ips space separated list of varnish ips to talk to
     * @param int $port the port the varnishes listen on (its the same port for all instances)
     */
    public function __construct($domain, array $ips, $port)
    {
        $url = parse_url($domain);
        $this->domain = $url['host'];
        if (isset($url['port'])) {
            $this->domain .= ':' . $url['port'];
        }
        $this->ips = $ips;
        $this->port = $port;
    }

    /**
     * Purge this absolute path at all registered cache server
     *
     * @param string $path Must be an absolute path
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    public function invalidatePath($path)
    {
        $request = "PURGE $path HTTP/1.0\r\n";
        $request.= "Host: {$this->domain}\r\n";
        $request.= "Connection: Close\r\n\r\n";

        $this->sendRequestToAllVarnishes($request);
    }

    /**
     * Force this absolute path to be refreshed 
     *
     * @param string $path Must be an absolute path
     * @throws \RuntimeException if connection to one of the varnish servers fails.
     */
    public function refreshPath($path)
    {
        $request = "GET $path HTTP/1.0\r\n";
        $request.= "Host: {$this->domain}\r\n";
        $request.= "Cache-Control: no-cache, no-store, max-age=0, must-revalidate";
        $request.= "Connection: Close\r\n\r\n";

        $this->sendRequestToAllVarnishes($request);
    }

    /**
     * Send a request to all configured varnishes
     *
     * @param string $request request string
     * @throws \RuntimeException if connection to one of the varnish servers fails. TODO: should we be more tolerant?
     */
    protected function sendRequestToAllVarnishes($request)
    {
        foreach ($this->ips as $ip) {
            $fp = fsockopen($ip, $this->port, $errno, $errstr, 2);
            if (!$fp) {
                throw new \RuntimeException("$errstr ($errno)");
            }

            fwrite($fp, $request);

            // read answer to the end, to be sure varnish is finished before continuing
            while (!feof($fp)) {
                fgets($fp, 128);
            }

            fclose($fp);
        }
    }
}
