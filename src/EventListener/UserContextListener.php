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

use FOS\HttpCache\UserContext\HashGenerator;
use FOS\HttpCacheBundle\Event\ReplayHeadersEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Check requests and responses with the matcher.
 *
 * Abort context hash requests immediately and return the hash.
 * Add the vary information on responses to normal requests.
 *
 * @author Stefan Paschke <stefan.paschke@gmail.com>
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class UserContextListener implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface
     */
    private $requestMatcher;

    /**
     * @var HashGenerator
     */
    private $hashGenerator;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $hash;

    /**
     * Constructor.
     *
     * @param RequestMatcherInterface      $requestMatcher
     * @param HashGenerator                $hashGenerator
     * @param array                        $options
     */
    public function __construct(
        RequestMatcherInterface $requestMatcher,
        HashGenerator $hashGenerator,
        array $options = []
    ) {
        $this->requestMatcher = $requestMatcher;
        $this->hashGenerator = $hashGenerator;

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'user_hash_header' => 'X-User-Context-Hash',
            'ttl' => 0,
            'add_vary_on_hash' => true,
        ]);
        $resolver->setRequired(['user_hash_header']);
        $resolver->setAllowedTypes('user_hash_header', 'string');
        $resolver->setAllowedTypes('ttl', 'int');
        $resolver->setAllowedTypes('add_vary_on_hash', 'bool');
        $resolver->setAllowedValues('user_hash_header', function ($value) {
            return strlen($value) > 0;
        });

        $this->options = $resolver->resolve($options);
    }

    /**
     * @param ReplayHeadersEvent $event
     */
    public function onReplayHeaders(ReplayHeadersEvent $event)
    {
        $this->hash = $this->hashGenerator->generateHash();

        $event->getHeaders()->set($this->options['user_hash_header'], $this->hash);
        $event->setTtl($this->options['ttl']);
    }

    /**
     * Add the context hash header to the headers to vary on if the header was
     * present in the request.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $vary = $response->getVary();

        if ($request->headers->has($this->options['user_hash_header'])) {
            // hash has changed, session has most certainly changed, prevent setting incorrect cache
            if (!is_null($this->hash) && $this->hash !== $request->headers->get($this->options['user_hash_header'])) {
                $response->setClientTtl(0);
                $response->headers->addCacheControlDirective('no-cache');

                return;
            }

            if ($this->options['add_vary_on_hash']
                && !in_array($this->options['user_hash_header'], $vary)
            ) {
                $vary[] = $this->options['user_hash_header'];
            }
        }

        $response->setVary($vary, true);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ReplayHeadersEvent::EVENT_NAME => 'onReplayHeaders',
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }
}
