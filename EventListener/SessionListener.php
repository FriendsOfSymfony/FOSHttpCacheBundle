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
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Decorates the default Symfony session listener.
 *
 * The default Symfony session listener automatically makes responses private
 * in case the session was started. This kills the user context feature of
 * FOSHttpCache. We disable the default behaviour only if the user context header
 * is part of the Vary headers to reduce the possible impacts on other parts
 * of your application.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
final class SessionListener implements EventSubscriberInterface
{
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
     * @param BaseSessionListener $inner
     * @param string              $userHashHeader        Must be lower-cased
     * @param array               $userIdentifierHeaders Must be lower-cased
     */
    public function __construct(BaseSessionListener $inner, $userHashHeader, array $userIdentifierHeaders)
    {
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

        $varyHeaders = array_map('strtolower', $event->getResponse()->getVary());
        $relevantHeaders = array_merge($this->userIdentifierHeaders, array($this->userHashHeader));

        // Call default behaviour if it's an irrelevant request for the user context
        if (0 === count(array_intersect($varyHeaders, $relevantHeaders))) {
            $this->inner->onKernelResponse($event);
        }

        // noop, see class description
    }

    public function onFinishRequest(FinishRequestEvent $event)
    {
        // this hook has been added in symfony 3.4.12 - older versions of the listener do not register for it
        $this->inner->onFinishRequest($event);
    }

    public static function getSubscribedEvents()
    {
        return BaseSessionListener::getSubscribedEvents();
    }
}
