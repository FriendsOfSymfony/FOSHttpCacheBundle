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

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Decorates the default Symfony session listener.
 *
 * The default Symfony session listener automatically makes responses private
 * in case the session was started. This breaks the user context feature of
 * FOSHttpCache. We disable the default behaviour only if the user context header
 * is part of the Vary headers to reduce the risk of impacts on other parts
 * of your application.
 *
 * @internal
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
final class SessionListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var BaseSessionListener
     */
    private $inner;

    /**
     * @var string
     */
    private $userHashHeader;

    /**
     * @var array
     */
    private $userIdentifierHeaders;

    /**
     * @param ContainerInterface  $container             To allow to be extra lazy
     * @param BaseSessionListener $inner
     * @param string              $userHashHeader        Must be lower-cased
     * @param array               $userIdentifierHeaders Must be lower-cased
     */
    public function __construct(
        ContainerInterface $container,
        BaseSessionListener $inner,
        string $userHashHeader,
        array $userIdentifierHeaders
    ) {
        $this->container = $container;
        $this->inner = $inner;
        $this->userHashHeader = $userHashHeader;
        $this->userIdentifierHeaders = $userIdentifierHeaders;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        return $this->inner->onKernelRequest($event);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        // copied from the Symfony SessionListener, to avoid breaking the lazyness
        if (!$session = $this->container && $this->container->has('initialized_session')
                ? $this->container->get('initialized_session')
                : $event->getRequest()->getSession()
        ) {
            return;
        }

        // Check if this response has a vary header that sounds like it is about the user context
        $varyHeaders = array_map('strtolower', $event->getResponse()->getVary());
        $relevantHeaders = array_merge($this->userIdentifierHeaders, [$this->userHashHeader]);

        if (0 < count(array_intersect($varyHeaders, $relevantHeaders))
            && ($session->isStarted() || ($session instanceof Session && $session->hasBeenStarted()))
        ) {
            $session->save();

            if ($session instanceof Session && $session->hasBeenStarted()) {
                // return early if this flag is set, because there is no way to reset it
                return;
            }
        }

        $this->inner->onKernelResponse($event);
    }

    public static function getSubscribedEvents(): array
    {
        return BaseSessionListener::getSubscribedEvents();
    }
}
