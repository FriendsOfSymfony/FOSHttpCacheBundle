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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
     * Used to exclude anonymous requests (no authentication nor session) from user hash sanity check.
     * It prevents issues when the hash generator that is used returns a customized value for anonymous users,
     * that differs from the documented, hardcoded one.
     *
     * @var RequestMatcherInterface
     */
    private $anonymousRequestMatcher;

    /**
     * Constructor.
     *
     * @param RequestMatcherInterface      $requestMatcher
     * @param HashGenerator                $hashGenerator
     * @param RequestMatcherInterface|null $anonymousRequestMatcher
     * @param array                        $options
     */
    public function __construct(
        RequestMatcherInterface $requestMatcher,
        HashGenerator $hashGenerator,
        RequestMatcherInterface $anonymousRequestMatcher = null,
        array $options = []
    ) {
        $this->requestMatcher = $requestMatcher;
        $this->hashGenerator = $hashGenerator;
        $this->anonymousRequestMatcher = $anonymousRequestMatcher;

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'user_identifier_headers' => ['Cookie', 'Authorization'],
            'user_hash_header' => 'X-User-Context-Hash',
            'ttl' => 0,
            'add_vary_on_hash' => true,
        ]);
        $resolver->setRequired(['user_identifier_headers', 'user_hash_header']);
        $resolver->setAllowedTypes('user_identifier_headers', 'array');
        $resolver->setAllowedTypes('user_hash_header', 'string');
        $resolver->setAllowedTypes('ttl', 'int');
        $resolver->setAllowedTypes('add_vary_on_hash', 'bool');
        $resolver->setAllowedValues('user_hash_header', function ($value) {
            return strlen($value) > 0;
        });

        $this->options = $resolver->resolve($options);
    }

    /**
     * Return the response to the context hash request with a header containing
     * the generated hash.
     *
     * If the ttl is bigger than 0, cache headers will be set for this response.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        if (!$this->requestMatcher->matches($event->getRequest())) {
            if ($event->getRequest()->headers->has($this->options['user_hash_header'])
                && !$this->isAnonymous($event->getRequest())
            ) {
                $this->hash = $this->hashGenerator->generateHash();
            }

            return;
        }

        $hash = $this->hashGenerator->generateHash();

        // status needs to be 200 as otherwise varnish will not cache the response.
        $response = new Response('', 200, [
            $this->options['user_hash_header'] => $hash,
            'Content-Type' => 'application/vnd.fos.user-context-hash',
        ]);

        if ($this->options['ttl'] > 0) {
            $response->setClientTtl($this->options['ttl']);
            $response->setVary($this->options['user_identifier_headers']);
            $response->setPublic();
        } else {
            $response->setClientTtl(0);
            $response->headers->addCacheControlDirective('no-cache');
        }

        $event->setResponse($response);
    }

    /**
     * Tests if $request is an anonymous request or not.
     *
     * For backward compatibility reasons, true will be returned if no anonymous request matcher was provided.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isAnonymous(Request $request)
    {
        return $this->anonymousRequestMatcher ? $this->anonymousRequestMatcher->matches($request) : false;
    }

    /**
     * Add the context hash header to the headers to vary on if the header was
     * present in the request.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $vary = $response->getVary();

        if ($request->headers->has($this->options['user_hash_header'])) {
            // hash has changed, session has most certainly changed, prevent setting incorrect cache
            if (!is_null($this->hash) && $this->hash !== $request->headers->get($this->options['user_hash_header'])) {
                $response->setCache([
                    'max_age' => 0,
                    's_maxage' => 0,
                    'private' => true,
                ]);
                $response->headers->addCacheControlDirective('no-cache');
                $response->headers->addCacheControlDirective('no-store');

                return;
            }

            if ($this->options['add_vary_on_hash']
                && !in_array($this->options['user_hash_header'], $vary)
            ) {
                $vary[] = $this->options['user_hash_header'];
            }
        } elseif ($this->options['add_vary_on_hash']) {
            /*
             * Additional precaution: If for some reason we get requests without a user hash, vary
             * on user identifier headers to avoid the caching proxy mixing up caches between
             * users. For the hash lookup request, those Vary headers are already added in
             * onKernelRequest above.
             */
            foreach ($this->options['user_identifier_headers'] as $header) {
                if (!in_array($header, $vary)) {
                    $vary[] = $header;
                }
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
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }
}
