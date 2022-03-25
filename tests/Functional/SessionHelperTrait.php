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
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides method to deal with sessions in a stateless container.
 */
trait SessionHelperTrait
{
    private function callInRequestContext(KernelBrowser $client, callable $callable)
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = self::$kernel->getContainer()->get('test.service_container')->get(EventDispatcherInterface::class);
        $return = null;
        $wrappedCallable = function (RequestEvent $event) use (&$callable, &$return) {
            try {
                $return = $callable($event);
            } finally {
                $event->setResponse(new Response(''));
                $event->stopPropagation();
            }
        };

        $eventDispatcher->addListener(KernelEvents::REQUEST, $wrappedCallable);
        try {
            $client->request('GET', '/'.uniqid('', true));

            return $return;
        } finally {
            $eventDispatcher->removeListener(KernelEvents::REQUEST, $wrappedCallable);
        }
    }
}
