<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\SymfonyCache;

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache as BaseHttpCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Base class for enhanced Symfony reverse proxy.
 * It can register event subscribers that may alter the request received.
 *
 * Use array returned by `getOptions()` to define which native/custom subscriber you want to use.
 * Possible keys are:
 * - "fos_native_subscribers": Bit options to define which native subscriber is needed.
 *   Defaults to `self::SUBSCRIBER_ALL`.
 *   Examples:
 *   - `self::SUBSCRIBER_USER_CONTEXT` (**only** user context).
 *   - `self::SUBSCRIBER_NONE` (**no** native subscriber).
 *   - `self::SUBSCRIBER_ALL | ~self::SUBSCRIBER_USER_CONTEXT` (**all** native ones **except** the user context one).
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
abstract class HttpCache extends BaseHttpCache
{
    /**
     * Options to indicate which native subscriber(s) to use.
     * They are to be used in the hash returned by getOptions(), with "fos_native_subscribers" key.
     *
     * These are bit options, use bitwise operators to combine them:
     * self::SUBSCRIBER_ALL | ~SUBSCRIBER_USER_CONTEXT => All but user context.
     * self::SUBSCRIBER_USER_CONTEXT | self::SUBSCRIBER_FOO => User context AND foo subscribers ONLY.
     */
    const SUBSCRIBER_ALL = -1; // Equals to ~0
    const SUBSCRIBER_NONE = 0;
    const SUBSCRIBER_USER_CONTEXT = 1;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        foreach ($this->getSubscribers() as $subscriber) {
            $this->addSubscriber($subscriber);
        }
    }

    /**
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * Add subscriber
     *
     * @param EventSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);

        return $this;
    }

    /**
     * Returns subscribers to be added to the event dispatcher, in respect to options defined in `getOptions()`.
     * Override this method if you want to add custom subscribers:
     *
     * ```php
     * protected function getSubscribers()
     * {
     *     // Get native subscribers.
     *     $subscribers = parent::getSubscribers();
     *     return array_merge($subscribers, [new CustomSubscriber(), new AnotherSubscriber()]);
     * }
     * ```
     *
     * @return EventSubscriberInterface[]
     */
    protected function getSubscribers()
    {
        $options = $this->getOptions();
        $subscribers = array();
        $nativeSubscribersOption = isset($options['fos_native_subscribers']) ? $options['fos_native_subscribers'] : self::SUBSCRIBER_ALL;
        if ($nativeSubscribersOption & self::SUBSCRIBER_USER_CONTEXT) {
            $subscribers[] = new UserContextSubscriber();
        }

        return $subscribers;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if ($this->getEventDispatcher()->hasListeners(Events::PRE_HANDLE)) {
            $event = new CacheEvent($this, $request);
            $this->getEventDispatcher()->dispatch(Events::PRE_HANDLE, $event);
            if ($event->getResponse()) {
                return $event->getResponse();
            }
        }

        return parent::handle($request, $type, $catch);
    }
}
