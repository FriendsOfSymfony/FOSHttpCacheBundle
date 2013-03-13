<?php

namespace Liip\CacheControlBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpFoundation\Session,
    Symfony\Component\HttpFoundation\Cookie,
    Symfony\Component\HttpFoundation\ResponseHeaderBag,
    Symfony\Component\HttpKernel\HttpKernel;

/**
 * This listener reads all flash messages and moves them into a cookie.
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class FlashMessageListener
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
     * @param   Session     $session A session instance
     * @param   array       $options
     */
    public function __construct($session, array $options = array())
    {
        $this->session = $session;
        $this->options = $options;
    }

   /**
    * Moves flash messages from the session to a cookie inside a Response Kernel listener
    *
    * @param EventInterface $event
    */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        $flashBag = $this->session->getFlashBag();
        $flashes = $flashBag->all();

        if (empty($flashes)) {
            return;
        }

        $response = $event->getResponse();

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        if (isset($cookies[$this->options['domain']][$this->options['path']][$this->options['name']])) {
            $rawCookie = $cookies[$this->options['domain']][$this->options['path']][$this->options['name']]->getValue();
            $flashes = array_merge($flashes, json_decode($rawCookie));
        }

        $cookie = new Cookie(
            $this->options['name'],
            json_encode($flashes),
            0,
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'],
            $this->options['httpOnly']
        );

        $response->headers->setCookie($cookie);
    }
}
