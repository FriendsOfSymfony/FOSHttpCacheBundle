<?php

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCache\UserContext\HashGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * listen to HEAD requests, return response after security, but before Controller is invoked
 *
 * @author Stefan Paschke <stefan.paschke@gmail.com>
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class UserContextSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface
     */
    private $requestMatcher;

    /**
     * @var HashGenerator
     */
    private $hashGenerator;

    /**
     * @var string
     */
    private $varyHeader;

    /**
     * @var string
     */
    private $hashHeader;

    /**
     * @var integer
     */
    private $ttl = 0;

    public function __construct(RequestMatcherInterface $requestMatcher, HashGenerator $hashGenerator, $varyHeader, $hashHeader, $ttl = 0)
    {
        $this->requestMatcher = $requestMatcher;
        $this->hashGenerator  = $hashGenerator;
        $this->varyHeader     = $varyHeader;
        $this->hashHeader     = $hashHeader;
        $this->ttl            = $ttl;
    }

    /**
     * Return the response to the HEAD Request with
     * the hash generated in a specified header
     *
     * If the ttl is superior to 0, cache headers
     * will be set for this response
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->requestMatcher->matches($event->getRequest())) {
            return;
        }

        $hash = $this->hashGenerator->generateHash();

        $response = new Response('', 200);
        $response->headers->set($this->hashHeader, $hash);

        if ($this->ttl > 0) {
            $response->setClientTtl($this->ttl);
            $response->setVary($this->varyHeader);
        } else {
            $response->setMaxAge(0);
        }

        $event->setResponse($response);
    }

    /**
     * Add a vary on the hash header to the response
     * when this header is present in the request
     *
     * If the response is not successful, the hash is not present,
     * or we are in the head request, the vary will not be set
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // Only set vary header when not on the HEAD request
        if ($this->requestMatcher->matches($event->getRequest())) {
            return;
        }

        // Only set vary header if we have the hash header
        if (!$event->getRequest()->headers->has($this->hashHeader)) {
            return;
        }

        $response = $event->getResponse();

        // Only set vary if response is successful
        if (!$response->isSuccessful()) {
            return;
        }

        $vary = $response->getVary();

        if (!in_array($this->hashHeader, $vary)) {
            $vary[] = $this->hashHeader;
        }

        $response->setVary($vary, true);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::REQUEST  => array('onKernelRequest', 7),
        );
    }
}
