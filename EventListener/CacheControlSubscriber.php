<?php

namespace FOS\HttpCacheBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
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
 * @author David Buchmann <mail@davidbu.ch>
 */
class CacheControlSubscriber implements EventSubscriberInterface
{
    /**
     * @var SecurityContextInterface|null to check unless_role
     */
    private $securityContext;

    /**
     * @var array List of arrays with RequestMatcherInterface, extra criteria array, header array.
     */
    private $map = array();

    /**
     * Cache control directives directly supported by Response.
     *
     * @var array
     */
    private $supportedDirectives = array(
        'max_age' => true,
        's_maxage' => true,
        'private' => true,
        'public' => true,
    );

    /**
     * If set, add a debug header to all responses, telling the cache proxy to
     * add debug output.
     *
     * @var string Name of the header or false to add no header.
     */
    protected $debugHeader;

    /**
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
     * Add a request matcher with a list of header directives.
     *
     * @param RequestMatcherInterface $requestMatcher The headers apply to requests matched by this.
     * @param array                   $extraCriteria  Must also be satisfied to match.
     * @param array                   $headers        An array of header configuration.
     * @param int                     $priority       Optional priority of this matcher. High values come first.
     */
    public function add(
        RequestMatcherInterface $requestMatcher,
        array $extraCriteria = array(),
        array $headers = array(),
        $priority = 0
    ) {
        if (!isset($this->map[$priority])) {
            $this->map[$priority] = array();
        }
        $this->map[$priority][] = array($requestMatcher, $extraCriteria, $headers);
    }

    /**
     * Apply the header rules if the request matches.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->debugHeader) {
            $response->headers->set($this->debugHeader, 1, false);
        }

        // do not change cache directives on unsafe requests.
        if (!$this->isRequestSafe($request)) {
            return;
        }

        $options = $this->getOptions($request, $response);
        if (false !== $options) {
            if (!empty($options['cache_control'])) {
                $directives = array_intersect_key($options['cache_control'], $this->supportedDirectives);
                $extraDirectives = array_diff_key($options['cache_control'], $directives);
                if (!empty($directives)) {
                    $response->setCache($directives);
                }
                if (!empty($extraDirectives)) {
                    $this->setExtraCacheDirectives($response, $extraDirectives);
                }
            }

            if (isset($options['reverse_proxy_ttl']) && null !== $options['reverse_proxy_ttl']) {
                $response->headers->set('X-Reverse-Proxy-TTL', (int) $options['reverse_proxy_ttl'], false);
            }

            if (!empty($options['vary'])) {
                $response->setVary(array_merge($response->getVary(), $options['vary']), true); //update if already has vary
            }

            if (isset($options['last_modified']) && null === $response->getLastModified()) {
                $response->setLastModified(new \DateTime($options['last_modified']));
            }
        }
    }

    /**
     * Add extra cache control directives.
     *
     * @param Response $response
     * @param array    $controls
     */
    protected function setExtraCacheDirectives(Response $response, array $controls)
    {
        $flags = array('must_revalidate', 'proxy_revalidate', 'no_transform', 'no_cache');
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
    }

    /**
     * Decide whether to even look for matching rules with the current request.
     *
     * @param Request $request
     *
     * @return bool True if the request is safe and headers can be set.
     */
    protected function isRequestSafe(Request $request)
    {
        return $request->isMethodSafe();
    }

    /**
     * Return the cache options for the current request if any rule matches.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return array|false of settings or false if nothing matched.
     */
    protected function getOptions(Request $request, Response $response)
    {
        foreach ($this->getRules() as $elements) {
            if ($this->matchExtraCriteria($elements[1], $request, $response)
                && $elements[0]->matches($request)
            ) {
                return $elements[2];
            }
        }

        return false;
    }

    /**
     * Check whether we match criteria that can not be expressed with the
     * request matcher.
     *
     * @param array    $criteria
     * @param Request  $request
     * @param Response $response
     *
     * @return bool Whether the criteria match
     */
    protected function matchExtraCriteria(array $criteria, Request $request, Response $response)
    {
        if (!empty($criteria['unless_role'])
            && $this->securityContext
            && $this->securityContext->isGranted($criteria['unless_role'])
        ) {
            return false;
        }

        if (!empty($criteria['match_response'])) {
            $expr = new ExpressionLanguage();
            if (!$expr->evaluate($criteria['match_response'], array(
                'response' => $response,
            ))) {
                return false;
            }
        } else {
            $status = array(200, 203, 300, 301, 302, 404, 410);
            if (!empty($criteria['additional_safe_status'])) {
                $status += $criteria['additional_safe_status'];
            }

            if (!in_array($response->getStatusCode(), $status)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the rules ordered by priority.
     *
     * @return array of array with matcher, extra criteria, headers
     */
    private function getRules()
    {
        $sortedRules = array();
        krsort($this->map);
        foreach ($this->map as $rules) {
            $sortedRules = array_merge($sortedRules, $rules);
        }

        return $sortedRules;

    }
}
