<?php

namespace Liip\CacheControlBundle\Response;

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
     * @param array                   $controls          An array of cache controls
     */
    public function add(RequestMatcherInterface $requestMatcher, array $controls = array())
    {
        $this->map[] = array($requestMatcher, $controls);
    }

   /**
    * On 'core.response' sets cache settings on the response object
    *
    * @param EventInterface $event
    */
    public function onCoreResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $controls = $this->getPatterns($request);

        if ($controls !== null) {
            $response->setCache($this->prepareControls($controls));
        }

        $vary = $response->getVary();
        if (! in_array('Cookie', $vary)) {
            $vary[] = 'Cookie';
            $response->setVary($vary, true); //update if already has vary
        }
    }

    /**
     * Return the cache controls for the current request
     * @param Request $request
     * @return array of settings
     */
    protected function getPatterns(Request $request)
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
