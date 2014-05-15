<?php

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
 * @author David Buchmann <mail@davidbu.ch>
 */
class CacheControlSubscriber implements EventSubscriberInterface
{
    /**
     * @var SecurityContextInterface|null to check unless_role
     */
    protected $securityContext;

    /**
     * @var array RequestMatcherInterface => header array.
     */
    protected $map = array();

    /**
     * Cache control directives directly supported by Response.
     *
     * @var array
     */
    protected $supportedDirectives = array(
        'etag' => true,
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
     * @param array                   $headers        An array of header configuration.
     */
    public function add(RequestMatcherInterface $requestMatcher, array $headers = array())
    {
        $this->map[] = array($requestMatcher, $headers);
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
        if (!$request->isMethodSafe()) {
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
     * Return the cache options for the current request.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return array|false of settings or false if nothing matched.
     */
    protected function getOptions(Request $request, Response $response)
    {
        foreach ($this->map as $elements) {
            if (!empty($elements[1]['unless_role'])
                && $this->securityContext
                && $this->securityContext->isGranted($elements[1]['unless_role'])
            ) {
                continue;
            }

            if ($elements[0]->matches($request)
                && $this->isResponseHandled($response, $elements[1])
            ) {
                return $elements[1];
            }
        }

        return false;
    }

    /**
     * Whether this response should be handled.
     *
     * @param Response $response
     * @param array    $options  Configuration that might influence the decision.
     *
     * @return bool
     */
    protected function isResponseHandled(Response $response, array $options)
    {
        /* We can't use Response::isCacheable because that also checks if cache
         * headers are already set. As we are about to set them, that would
         * always return false.
         */
        return in_array($response->getStatusCode(), array(200, 203, 300, 301, 302, 404, 410));
    }
}
