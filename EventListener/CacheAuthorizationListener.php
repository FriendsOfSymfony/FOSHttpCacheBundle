<?php

namespace FOS\HttpCacheBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * listen to HEAD requests, return response after security, but before Controller is invoked
 *
 * @author Stefan Paschke stefan.paschke@gmail.com
 */
class CacheAuthorizationListener
{
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
