<?php

namespace Liip\CacheControlBundle\Request;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * listen to HEAD requests, return response after security, but before Controller is invoked
 *
 * 
 *
 * @author Stefan Paschke stefan.paschke@gmail.com
 */
class CacheAuthorizationListener
{
   /**
    * On 'core.request'
    *
    * @param EventInterface $event
    */
    public function onCoreRequest(GetResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if ($request->getMethod() == 'HEAD') {
            die;
        }
    }
}
