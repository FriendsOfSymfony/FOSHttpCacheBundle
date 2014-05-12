<?php

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCache\UserContext\HashGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
     * @var string[]
     */
    private $userIdentifierHeaders;

    /**
     * @var string
     */
    private $hashHeader;

    /**
     * @var integer
     */
    private $ttl;

    public function __construct(
        RequestMatcherInterface $requestMatcher,
        HashGenerator $hashGenerator,
        $userIdentifierHeaders = array('Vary', 'Authorization'),
        $hashHeader = "X-User-Context-Hash",
        $ttl = 0
    )
    {
        $this->requestMatcher        = $requestMatcher;
        $this->hashGenerator         = $hashGenerator;
        $this->userIdentifierHeaders = $userIdentifierHeaders;
        $this->hashHeader            = $hashHeader;
        $this->ttl                   = $ttl;
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
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        if (!$this->requestMatcher->matches($event->getRequest())) {
            return;
        }

        $hash = $this->hashGenerator->generateHash();

        $response = new Response('', 200, array(
            $this->hashHeader => $hash,
            'Content-Type'    => 'application/vnd.fos.user-context-hash'
        ));

        if ($this->ttl > 0) {
            $response->setClientTtl($this->ttl);
            $response->setVary($this->userIdentifierHeaders);
        } else {
            $response->setClientTtl(0);
            $response->headers->addCacheControlDirective('no-cache');
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
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

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
