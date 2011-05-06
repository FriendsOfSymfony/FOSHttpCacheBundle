<?php

namespace Liip\CacheControlBundle\Helper;

/**
 * Helper to invalidate varnish entries (purge)
 *
 * Uses PURGE requests to the frontend. Supports multiple varnish instances.
 *
 * To set up varnish, you need to configure it accordingly.
 * add the following code

#top level:
# who is allowed to purge from cache
# http://varnish-cache.org/trac/wiki/VCLExamplePurging
acl purge {
        "127.0.0.1"; #localhost for dev purposes
        "10.0.11.0"/24; #server closed network
}

#in sub vcl_recv
# purge if client is in correct ip range
if (req.request == "PURGE") {
    if (!client.ip ~ purge) {
        error 405 "Not allowed.";
    }
    purge("req.url ~ " req.url);
    #log "PURGE " req.url;
    error 200 "Success";
}

 * NOTE: this code invalidates the url for all domains. If your varnish serves
 * multiple domains, you should improve this configuration.
 * Pull requests welcome :-)
 *
 * This is about equivalent to doing this

     netcat localhost 6081 << EOF
     PURGE /url/to/purge HTTP/1.1
     host: webapp-host.name

     EOF

 *
 * TODO: would be nice to support the varnish admin shell as well. It would be
 * more clean and secure, but you have to configure varnish accordingly. By default
 * the admin port is only open for local host for security reasons.
 */
class Varnish
{
    private $domain;
    private $varnishes;
    private $port;

    /**
     * Constructor
     *
     * @param string $domain the domain we want to purge urls from. only domain and port are used, path is ignored
     * @param array $varnishes space separated list of varnish ips to talk to
     * @param int $port the port the varnishes listen on (its the same port for all instances)
     */
    public function __construct($domain, $varnishes, $port)
    {
        $url = parse_url($domain);
        $this->domain = $url['host'];
        if (isset($url['port'])) {
            $this->domain .= ':' . $url['port'];
        }
        $this->varnishes = array_map("trim", explode(' ', $varnishes));
        $this->port = $port;
    }

    /**
     * Purge this absolute path at all registered cache server
     *
     * @param $path Must be an absolute path
     * @throw Exception if connection to one of the varnish servers fails. TODO: should we be more tolerant?
     */
    public function invalidatePath($path)
    {
        foreach($this->varnishes as $ip) {
            $fp = fsockopen($ip, $this->port, $errno, $errstr, 2);
            if (!$fp) {
                throw new Exception("$errstr ($errno)");
            } else {
                $out = "PURGE $path HTTP/1.0\r\n";
                $out .= "Host: {$this->domain}\r\n";
                $out .= "Connection: Close\r\n\r\n";
                fwrite($fp, $out);

                //read answer to the end, to be sure varnish is finished before continuing
                while (!feof($fp)) {
                    fgets($fp, 128);
                }

                fclose($fp);
            }
        }
    }
}
