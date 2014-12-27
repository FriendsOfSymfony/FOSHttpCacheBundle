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

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Base class for enhanced Symfony reverse proxy based on the symfony FrameworkBundle HttpCache.
 *
 * This kernel supports event subscribers that can act on the events defined in
 * FOS\HttpCache\SymfonyCache\Events and may alter the request flow.
 *
 * This kernel can auto-register subscribers. Overwrite `getOptions()` in your cache kernel to
 * return an array with the key "fos_default_subscribers". The value is a bit mask to define which
 * subscribers should be added with default options. If nothing is specified, the value is
 * self::SUBSCRIBER_ALL.
 *
 * Examples:
 *   - `self::SUBSCRIBER_USER_CONTEXT` (**only** user context).
 *   - `self::SUBSCRIBER_NONE` (**no** native subscriber).
 *   - `self::SUBSCRIBER_ALL | ~self::SUBSCRIBER_USER_CONTEXT` (**all** native ones **except** the user context one).
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
abstract class EventDispatchingHttpCache extends HttpCache
{
    /**
     * Option for the "fos_default_subscribers" that enables all subscribers.
     *
     * See class phpdoc for examples.
     */
    const SUBSCRIBER_ALL = -1; // Equals to ~0

    /**
     * Option for the "fos_default_subscribers" that enables no subscribers.
     *
     * See class phpdoc for examples.
     */
    const SUBSCRIBER_NONE = 0;

    /**
     * Option for the "fos_default_subscribers" that enables the user context subscriber.
     *
     * See class phpdoc for examples.
     */
    const SUBSCRIBER_USER_CONTEXT = 1;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritDoc}
     *
     * Adding the default subscribers to the event dispatcher.
     */
    public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        foreach ($this->getDefaultSubscribers() as $subscriber) {
            $this->addSubscriber($subscriber);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Adding the Events::PRE_HANDLE event.
     */
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

    /**
     * Add subscriber
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);
    }

    /**
     * Return the subscribers to be added to the event dispatcher, according to the
     * fos_default_subscribers option in `getOptions()`.
     *
     * Override this method if you want to customize subscribers or add your own subscribers.
     *
     * @return EventSubscriberInterface[]
     */
    protected function getDefaultSubscribers()
    {
        $options = $this->getOptions();
        $subscribers = array();
        $defaultSubscribersOption = isset($options['fos_default_subscribers']) ? $options['fos_default_subscribers'] : self::SUBSCRIBER_ALL;
        if ($defaultSubscribersOption & self::SUBSCRIBER_USER_CONTEXT) {
            $subscribers[] = new UserContextSubscriber();
        }

        return $subscribers;
    }

    /**
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }
}
