<?php

/*
 * Found from Jérémy Derussé
 *
 * https://github.com/jderusse/symfony/blob/7847cf8417447d5ca9f59695869a493b3cac4dca/src/Symfony/Bundle/FrameworkBundle/Test/SessionHelperTrait.php
 */

namespace FOS\HttpCacheBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides method to deal with sessions in a stateless container.
 */
trait SessionHelperTrait
{
    private function callInRequestContext(KernelBrowser $client, callable $callable)
    {
        $container = method_exists($this, 'getContainer') ? self::getContainer() : (property_exists($this, 'container') ? self::$container : $client->getContainer());
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = Kernel::MAJOR_VERSION < 5
            ? $container->get(EventDispatcherInterface::class)
            : self::$kernel->getContainer()->get('test.service_container')->get(EventDispatcherInterface::class)
        ;
        $wrappedCallable = function (RequestEvent $event) use (&$callable) {
            try {
                $callable($event);
            } finally {
                $event->setResponse(new Response(''));
                $event->stopPropagation();
            }
        };

        // we must only listen to the event after the firewall security listener has upgraded the user, as that will migrate the session, changing the id.
        $eventDispatcher->addListener(KernelEvents::REQUEST, $wrappedCallable);
        try {
            // this must request an existing url, otherwise the event we handled will be a not found exception, and not a regular request
            $client->request('GET', '/secured_area/_fos_user_context_hash?cachebust='.uniqid('', true));
        } finally {
            $eventDispatcher->removeListener(KernelEvents::REQUEST, $wrappedCallable);
        }
    }
}
