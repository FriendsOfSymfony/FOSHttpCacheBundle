<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ReplayHeadersEvent
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class ReplayHeadersEvent extends Event
{
    const EVENT_NAME = 'fos.replay_headers';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ResponseHeaderBag
     */
    private $headers;

    /**
     * @var int
     */
    private $ttl = 0;

    /**
     * ReplayHeadersEvent constructor.
     *
     * @param Request           $request
     * @param ResponseHeaderBag $headers
     */
    public function __construct(Request $request, ResponseHeaderBag $headers)
    {
        $this->request = $request;
        $this->headers = $headers;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Sets the TTL for the replay headers response.
     * 0 equals no-cache. Otherwise the lowest set value of all
     * event listeners will be taken into account.
     *
     * @param int $ttl
     *
     * @return $this
     */
    public function setTtl($ttl)
    {
        if (0 === $ttl || 0 === $this->ttl) {
            $this->ttl = $ttl;

            return $this;
        }

        // Only set it if lower than existing TTL
        if ($ttl < $this->ttl) {
            $this->ttl = $ttl;
        }

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseHeaderBag
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
