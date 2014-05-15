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
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Set caching settings on the response according to the configurations.
 *
 * Allowed options are found in Symfony\Component\HttpFoundation\Response::setCache
 *
 * @author Lea Haensenberger <lea.haensenberger@gmail.com>
 */
class CacheControlSubscriber implements EventSubscriberInterface
{
    /**
     * @var SecurityContextInterface
     *
     * Security Context
     */
    protected $securityContext;

    /**
     * @var array
     *
     * Map
     */
    protected $map = array();

    /**
     * @var array
     *
     * supported headers from Response
     */
    protected $supportedHeaders = array(
        'etag' => true,
        'last_modified' => true,
        'max_age' => true,
        's_maxage' => true,
        'private' => true,
        'public' => true,
    );

    /**
     * Add debug header to all responses, telling the cache proxy to add debug
     * output.
     *
     * @var string Name of the header or false to add no header.
     */
    protected $debugHeader;

    /**
     * Constructor.
     *
     * @param SecurityContextInterface $securityContext Used to handle unless_role criteria. (optional)
     * @param Boolean                  $debugHeader     Header to add for debugging, or false to send no header. (optional)
     */
    public function __construct(SecurityContextInterface $securityContext = null, $debugHeader = false)
    {
        $this->securityContext = $securityContext;
        $this->debugHeader = $debugHeader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
        );
    }

    /**
     * Add request matcher to map
     *
     * @param RequestMatcherInterface $requestMatcher A RequestMatcherInterface instance
     * @param array                   $options        An array of options
     *
     * @return CacheControlSubscriber self Object
     */
    public function add(RequestMatcherInterface $requestMatcher, array $options = array())
    {
        $this->map[] = array($requestMatcher, $options);

        return $this;
    }

    /**
     * On kernel response event subscriber method
     *
     * @param FilterResponseEvent $event Event
     *
     * @return CacheControlSubscriber self Object
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        if ($this->debugHeader) {
            $response->headers->set($this->debugHeader, 1, false);
        }

        $options = $this->getOptions($event->getRequest());
        if (false !== $options) {
            if (!empty($options['controls'])) {
                $controls = array_intersect_key($options['controls'], $this->supportedHeaders);
                $extraControls = array_diff_key($options['controls'], $controls);

                //set supported headers
                if (!empty($controls)) {
                    $response->setCache($this->prepareControls($controls));
                }

                //set extra headers, f.e. varnish specific headers
                if (!empty($extraControls)) {
                    $this->setExtraControls($response, $extraControls);
                }
            }

            if (isset($options['reverse_proxy_ttl']) && null !== $options['reverse_proxy_ttl']) {
                $response->headers->set('X-Reverse-Proxy-TTL', (int) $options['reverse_proxy_ttl'], false);
            }

            if (!empty($options['vary'])) {
                $response->setVary(array_merge($response->getVary(), $options['vary']), true); //update if already has vary
            }
        }

        return $this;
    }

    /**
     * adds extra cache controls
     *
     * @param Response $response Response
     * @param array    $controls Controls
     *
     * @return CacheControlSubscriber self Object
     */
    protected function setExtraControls(Response $response, array $controls)
    {
        $flags = array('must_revalidate', 'proxy_revalidate', 'no_transform');
        $options = array('stale_if_error', 'stale_while_revalidate');

        foreach ($flags as $flag) {
            if (!empty($controls[$flag])) {
                $response->headers->addCacheControlDirective(str_replace('_', '-', $flag));
            }
        }

        foreach ($options as $option) {
            if (!empty($controls[$option])) {
                $response->headers->addCacheControlDirective(str_replace('_', '-', $option), $controls[$option]);
            }
        }

        if (!empty($controls['no_cache'])) {
            $response->headers->remove('Cache-Control');
            $response->headers->set('Cache-Control','no-cache', true);
        }

        return $this;
    }

    /**
     * Return the cache options for the current request
     *
     * @param Request $request Request
     *
     * @return array Settings array
     */
    protected function getOptions(Request $request)
    {
        foreach ($this->map as $elements) {
            if (!empty($elements[1]['unless_role'])
                && $this->securityContext
                && $this->securityContext->isGranted($elements[1]['unless_role'])
            ) {
                continue;
            }

            if ($elements[0]->matches($request)) {
                return $elements[1];
            }
        }

        return false;
    }

    /**
     * Create php values for needed controls
     *
     * @param array $controls Controls
     *
     * @return array Controls
     */
    protected function prepareControls(array $controls)
    {
        if (isset($controls['last_modified'])) {
            // this must be a DateTime, convert from the string in configuration
            $controls['last_modified'] = new \DateTime($controls['last_modified']);
        }

        return $controls;
    }
}
