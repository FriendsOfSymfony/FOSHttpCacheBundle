<?php

namespace Liip\CacheControlBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set caching settings on the reponse according to the app config
 *
 * Allowed options are found in Symfony\Component\HttpFoundation\Response::setCache
 *
 * @author Lea Haensenberger <lea.haensenberger@gmail.com>
 */
class CacheControlListener
{
    protected $map = array();

    /**
     * Constructor.
     *
     * @param RequestMatcherInterface $requestMatcher A RequestMatcherInterface instance
     * @param array                   $options        An array of options
     */
    public function add(RequestMatcherInterface $requestMatcher, array $options = array())
    {
        $this->map[] = array($requestMatcher, $options);
    }

   /**
    * @param FilterResponseEvent $event
    */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if ($options = $this->getOptions($request)) {
            if (!empty($options['controls'])) {
                $supportedHeaders = array('etag', 'last_modified', 'max_age', 's_maxage', 'private', 'public');
                $supportedHeaders = array_combine($supportedHeaders, $supportedHeaders);

                $this->setDefaultHeaders($supportedHeaders, $options, $response);
                $this->setExtraHeaders($supportedHeaders, $options, $response);
            }

            if (isset($options['reverse_proxy_ttl']) && null !== $options['reverse_proxy_ttl']) {
                $response->headers->set('X-Reverse-Proxy-TTL', (int) $options['reverse_proxy_ttl'], false);
            }

            if (!empty($options['vary'])) {
                $response->setVary(array_merge($response->getVary(), $options['vary']), true); //update if already has vary
            }
        }
    }

    /**
     * sets the supported headers on Response
     *
     * @param array $supportedHeaders
     * @param array $options
     * @param Response $response
     */
    private function setDefaultHeaders(array $supportedHeaders, array $options, Response $response)
    {
        //filter all headers that are supported by Response::setCache
        $controls = array_intersect_ukey($options['controls'], $supportedHeaders, function($key1, $key2) {
            if ($key1 == $key2) {
                return 0;
            } else if ($key1 > $key2) {
                return 1;
            } else {
                return -1;
            }
        });

        if (!empty($controls)) {
            $response->setCache($this->prepareControls($controls));
        }
    }

    /**
     * sets extra headers not supported headers on Response
     *
     * @param array $supportedHeaders
     * @param array $options
     * @param Response $response
     */
    private function setExtraHeaders(array $supportedHeaders, array $options, Response $response)
    {
        //filter all headers that are not yet supported by Response::setCache
        $extraControls = array_intersect_ukey($options['controls'], $supportedHeaders, function($key1, $key2) {
            if ($key1 == $key2) {
                return -1;
            } else {
                return 0;
            }
        });

        if (!empty($extraControls)) {
            $this->setExtraControls($response, $extraControls);
        }
    }

    /**
     * adds extra cache controls
     *
     * @param Response $response
     * @param $controls
     */
    protected function setExtraControls(Response $response, array $controls)
    {
        if (!empty($controls['must_revalidate'])) {
            $response->headers->addCacheControlDirective('must-revalidate', $controls['must_revalidate']);
        }

        if (!empty($controls['proxy_revalidate'])) {
            $response->headers->addCacheControlDirective('proxy-revalidate', true);
        }

        if (!empty($controls['no_transform'])) {
            $response->headers->addCacheControlDirective('no-transform', true);
        }

        if (!empty($controls['stale_if_error'])) {
            $response->headers->addCacheControlDirective('stale-if-error='.$controls['stale_if_error'], true);
        }

        if (!empty($controls['stale_while_revalidate'])) {
            $response->headers->addCacheControlDirective('stale-while-revalidate='.$controls['stale_while_revalidate'], true);
        }

        if (!empty($controls['no_cache'])) {
            $response->headers->remove('Cache-Control');
            $response->headers->set('Cache-Control','no-cache', true);
        }
    }

    /**
     * Return the cache options for the current request
     *
     * @param Request $request
     * @return array of settings
     */
    protected function getOptions(Request $request)
    {
        foreach ($this->map as $elements) {
            if (null === $elements[0] || $elements[0]->matches($request)) {
                return $elements[1];
            }
        }

        return null;
    }

    /**
     * Create php values for needed controls
     *
     * @param array $controls
     * @return array
     */
    protected function prepareControls($controls)
    {
        if (isset($controls['last_modified'])) {
            //this must be a DateTime, convert from the string in configuration
            $controls['last_modified'] = new \DateTime($controls['last_modified']);
        }

        return $controls;
    }
}
