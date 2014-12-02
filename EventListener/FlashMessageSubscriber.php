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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This event handler reads all flash messages and moves them into a cookie.
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class FlashMessageSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Session
     */
    private $session;

    /**
     * Set a serializer instance
     *
     * @param Session $session A session instance
     * @param array   $options
     */
    public function __construct($session, array $options = array())
    {
        $this->session = $session;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
        );
    }

    /**
     * Moves flash messages from the session to a cookie inside a Response Kernel listener
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        // Flash messages are stored in the session. If there is none, there
        // can't be any flash messages in it. $session->getFlashBag() would
        // create a session, we need to avoid that.
        if (!$this->session->isStarted()) {
            return;
        }

        $flashBag = $this->session->getFlashBag();
        $flashes = $flashBag->all();

        if (empty($flashes)) {
            return;
        }

        $response = $event->getResponse();

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        if (isset($cookies[$this->options['host']][$this->options['path']][$this->options['name']])) {
            $rawCookie = $cookies[$this->options['host']][$this->options['path']][$this->options['name']]->getValue();
            $flashes = array_merge($flashes, json_decode($rawCookie));
        }

        $cookie = new Cookie(
            $this->options['name'],
            json_encode($flashes),
            0,
            $this->options['path'],
            $this->options['host'],
            $this->options['secure'],
            false
        );

        $response->headers->setCookie($cookie);
    }
}
