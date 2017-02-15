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

use FOS\HttpCacheBundle\Event\ReplayHeadersEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Extracts information from a request and replicates them onto a response
 * by allowing other event listeners to add their headers.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class ReplayHeadersListener implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $identifyingHeaders;

    /**
     * Constructor.
     *
     * @param RequestMatcherInterface $requestMatcher
     * @param array                   $identifyingHeaders
     */
    public function __construct(
        RequestMatcherInterface $requestMatcher,
        array $identifyingHeaders = ['Cookie', 'Authorization']
    ) {
        $this->requestMatcher = $requestMatcher;
        $this->identifyingHeaders = $identifyingHeaders;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (!$this->matchesRequest($event->getRequest())) {
            return;
        }

        $replayEvent = new ReplayHeadersEvent($event->getRequest(), new ResponseHeaderBag());
        $this->eventDispatcher->dispatch(ReplayHeadersEvent::EVENT_NAME, $replayEvent);

        $headers = $replayEvent->getHeaders();
        $response = new Response('', 200, ['Content-Type' => 'application/vnd.fos.replay_headers']);

        // TTL
        $ttl = $replayEvent->getTtl();

        if ($ttl > 0) {
            $response->setClientTtl($ttl);
            $response->setPublic();
        } else {
            $response->setClientTtl(0);
            $response->headers->addCacheControlDirective('no-cache');
        }

        if (0 === count($headers)) {
            $event->setResponse($response);
            return;
        }

        $replayHeaders = [];
        foreach ($headers->all() as $k => $v) {
            $replayHeaders[] = $k;
            $response->headers->set($k, $v);
        }

        $response->headers->set('FOS-Replay-Headers', implode(',', array_unique($replayHeaders)));
        $event->setResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // TODO: what priority?
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function matchesRequest(Request $request)
    {
        if ('GET' !== $request->getMethod()) {
            return false;
        }

        $hasIdentifyingHeader = false;
        foreach ($this->identifyingHeaders as $identifyingHeader) {
            if ('cookie' === strtolower($identifyingHeader) && $request->cookies->count() > 0) {
                $hasIdentifyingHeader = true;
                continue;
            }

            if ($request->headers->has($identifyingHeader)) {
                $hasIdentifyingHeader = true;
            }
        }

        if (!$hasIdentifyingHeader) {
            return false;
        }

        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));

        if (!$acceptHeader->has('Content-Type')) {
            return false;
        }

        if ('application/vnd.fos.replay_headers' !== $acceptHeader->get('Content-Type')) {
            return false;
        }

        return true;
    }
}
