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
use Symfony\Component\HttpFoundation\Request;
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
     * @var int
     */
    private $ttl;

    /**
     * Used to exclude anonymous requests (no authentication nor session) from user hash sanity check.
     * It prevents issues when the hash generator that is used returns a customized value for anonymous users,
     * that differs from the documented, hardcoded one.
     *
     * @var RequestMatcherInterface
     */
    private $anonymousRequestMatcher;

    public function __construct(
        RequestMatcherInterface $requestMatcher,
        HashGenerator $hashGenerator,
        array $userIdentifierHeaders = array('Cookie', 'Authorization'),
        $hashHeader = 'X-User-Context-Hash',
        $ttl = 0,
        RequestMatcherInterface $anonymousRequestMatcher = null
    ) {
        if (!count($userIdentifierHeaders)) {
            throw new \InvalidArgumentException('The user context must vary on some request headers');
        }
        $this->requestMatcher = $requestMatcher;
        $this->hashGenerator = $hashGenerator;
        $this->userIdentifierHeaders = $userIdentifierHeaders;
        $this->hashHeader = $hashHeader;
        $this->ttl = $ttl;
        $this->anonymousRequestMatcher = $anonymousRequestMatcher;
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
            if ($event->getRequest()->headers->has($this->hashHeader) && !$this->isAnonymous($event->getRequest())) {
                $this->hash = $this->hashGenerator->generateHash();
            }

            return;
        }

        $hash = $this->hashGenerator->generateHash();

        // status needs to be 200 as otherwise varnish will not cache the response.
        $response = new Response('', 200, array(
            $this->hashHeader => $hash,
            'Content-Type' => 'application/vnd.fos.user-context-hash',
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
     * Tests if $request is an anonymous request or not.
     *
     * For backward compatibility reasons, true will be returned if no anonymous request matcher was provided.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isAnonymous(Request $request)
    {
        return $this->anonymousRequestMatcher ? $this->anonymousRequestMatcher->matches($request) : false;
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
            KernelEvents::REQUEST => array('onKernelRequest', 7),
        );
    }
}
