<?php

namespace FOS\HttpCacheBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

use FOS\HttpCacheBundle\Http\RuleMatcherInterface;

/**
 * Set caching settings on matching response according to the configurations.
 *
 * The first matching ruleset is applied.
 *
 * @author Lea Haensenberger <lea.haensenberger@gmail.com>
 * @author David Buchmann <mail@davidbu.ch>
 */
class CacheControlSubscriber implements EventSubscriberInterface
{
    /**
     * @var array List of arrays with RuleMatcher, header array.
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
     * If not empty, add a debug header with that name to all responses,
     * telling the cache proxy to add debug output.
     *
     * @var string Name of the header or false to add no header.
     */
    protected $debugHeader;

    /**
     * @param string|bool $debugHeader Header to set to trigger debugging, or false to send no header.
     */
    public function __construct($debugHeader = false)
    {
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
     * Add a rule matcher with a list of header directives to apply if the
     * request and response are matched.
     *
     * @param RuleMatcherInterface $ruleMatcher The headers apply to responses matched by this matcher.
     * @param array                $headers     An array of header configuration.
     * @param int                  $priority    Optional priority of this matcher. Higher priority is applied first.
     */
    public function add(
        RuleMatcherInterface $ruleMatcher,
        array $headers = array(),
        $priority = 0
    ) {
        if (!isset($this->map[$priority])) {
            $this->map[$priority] = array();
        }
        $this->map[$priority][] = array($ruleMatcher, $headers);
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
            if ($elements[0]->matches($request, $response)) {
                return $elements[1];
            }
        }

        return false;
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
