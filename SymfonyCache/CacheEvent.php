<?php

namespace FOS\HttpCacheBundle\SymfonyCache;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache as BaseHttpCache;

class CacheEvent extends Event
{
    /**
     * @var HttpCache
     */
    private $kernel;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param BaseHttpCache $kernel  The kernel raising with this event.
     * @param Request   $request The request being processed.
     */
    public function __construct(BaseHttpCache $kernel, Request $request)
    {
        $this->kernel = $kernel;
        $this->request = $request;
    }

    /**
     * Get the kernel that raised this event.
     *
     * @return HttpCache
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Get the request that is being processed.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response|null The response if one was set.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets a response to use instead of continuing to handle this request.
     *
     * Setting a response stops propagation of the event.
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        $this->stopPropagation();
    }
}
