<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listen to HEAD requests, return response after security, but before
 * Controller is invoked.
 *
 * @author Stefan Paschke <stefan.paschke@gmail.com>
 */
class CacheAuthorizationSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->getMethod() == 'HEAD') {
            // return a 200 "OK" Response to stop processing
            $response = new Response('', 200);
            $event->setResponse($response);
        }
    }
}
