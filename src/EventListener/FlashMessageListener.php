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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This event handler reads all flash messages and moves them into a cookie.
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
final class FlashMessageListener implements EventSubscriberInterface
{
    private array $options;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['name', 'path', 'host', 'secure']);
        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('path', 'string');
        $resolver->setAllowedTypes('host', ['string', 'null']);
        $resolver->setAllowedTypes('secure', 'bool');
        $this->options = $resolver->resolve($options);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Moves flash messages from the session to a cookie inside a Response Kernel listener.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        try {
            $session = $event->getRequest()->getSession();
        } catch (SessionNotFoundException) {
            return;
        }
        if (!($session instanceof FlashBagAwareSessionInterface)) {
            return;
        }

        // Flash messages are stored in the session. If there is none, there
        // can't be any flash messages in it. $session->getFlashBag() would
        // create a session, we need to avoid that.
        if (!$session->isStarted()) {
            return;
        }

        $flashBag = $session->getFlashBag();
        $flashes = $flashBag->all();

        if (empty($flashes)) {
            return;
        }

        $response = $event->getResponse();

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $host = $this->options['host'] ?? '';
        if (isset($cookies[$host][$this->options['path']][$this->options['name']])) {
            $rawCookie = $cookies[$host][$this->options['path']][$this->options['name']]->getValue();
            $flashes = array_merge_recursive($flashes, json_decode($rawCookie, true, 512, JSON_THROW_ON_ERROR));
        }

        // Preserve existing flash message cookie from previous redirect if there was one.
        // This covers multiple redirects where each redirect adds flash messages.
        $request = $event->getRequest();
        if ($request->cookies->has($this->options['name'])) {
            $rawCookie = $request->cookies->get($this->options['name']);
            $flashes = array_merge_recursive($flashes, json_decode($rawCookie, true, 512, JSON_THROW_ON_ERROR));
        }

        $cookie = new Cookie(
            $this->options['name'],
            json_encode($flashes, JSON_THROW_ON_ERROR),
            0,
            $this->options['path'],
            $this->options['host'],
            $this->options['secure'],
            false
        );

        $response->headers->setCookie($cookie);
    }
}
