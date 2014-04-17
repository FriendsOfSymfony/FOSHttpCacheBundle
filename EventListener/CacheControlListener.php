<?php

namespace FOS\HttpCacheBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Set caching settings on the response according to the configurations.
 *
 * Allowed options are found in Symfony\Component\HttpFoundation\Response::setCache
 *
 * @author Lea Haensenberger <lea.haensenberger@gmail.com>
 */
class CacheControlListener
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $map = array();

    /**
     * supported headers from Response
     *
     * @var array
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
    }

    /**
     * adds extra cache controls
     *
     * @param Response $response
     * @param $controls
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
    }

    /**
     * Return the cache options for the current request
     *
     * @param Request $request
     *
     * @return array of settings
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
     * @param array $controls
     * @return array
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
