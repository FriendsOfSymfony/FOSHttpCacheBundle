<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
 * Check requests and responses with the matcher.
 *
 * Abort context hash requests immediately and return the hash.
 * Add the vary information on responses to normal requests.
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
    private $hash;

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
        array $userIdentifierHeaders = array('Cookie', 'Authorization'),
        $hashHeader = "X-User-Context-Hash",
        $ttl = 0
    ) {
        if (!count($userIdentifierHeaders)) {
            throw new \InvalidArgumentException('The user context must vary on some request headers');
        }
        $this->requestMatcher        = $requestMatcher;
        $this->hashGenerator         = $hashGenerator;
        $this->userIdentifierHeaders = $userIdentifierHeaders;
        $this->hashHeader            = $hashHeader;
        $this->ttl                   = $ttl;
    }

    /**
     * Return the response to the context hash request with a header containing
     * the generated hash.
     *
     * If the ttl is bigger than 0, cache headers will be set for this response.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        if (!$this->requestMatcher->matches($event->getRequest())) {
            if ($event->getRequest()->headers->has($this->hashHeader)) {
                $this->hash = $this->hashGenerator->generateHash();
            }

            return;
        }

        $hash = $this->hashGenerator->generateHash();

        // status needs to be 200 as otherwise varnish will not cache the response.
        $response = new Response('', 200, array(
            $this->hashHeader => $hash,
            'Content-Type'    => 'application/vnd.fos.user-context-hash',
        ));

        if ($this->ttl > 0) {
            $response->setClientTtl($this->ttl);
            $response->setVary($this->userIdentifierHeaders);
            $response->setPublic();
        } else {
            $response->setClientTtl(0);
            $response->headers->addCacheControlDirective('no-cache');
        }

        $event->setResponse($response);
    }

    /**
     * Add the context hash header to the headers to vary on if the header was
     * present in the request.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $vary = $response->getVary();

        if ($request->headers->has($this->hashHeader)) {
            // hash has changed, session has most certainly changed, prevent setting incorrect cache
            if (!is_null($this->hash) && $this->hash !== $request->headers->get($this->hashHeader)) {
                $response->setClientTtl(0);
                $response->headers->addCacheControlDirective('no-cache');

                return;
            }

            if (!in_array($this->hashHeader, $vary)) {
                $vary[] = $this->hashHeader;
            }
        } else {
            foreach ($this->userIdentifierHeaders as $header) {
                if (!in_array($header, $vary)) {
                    $vary[] = $header;
                }
            }
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
