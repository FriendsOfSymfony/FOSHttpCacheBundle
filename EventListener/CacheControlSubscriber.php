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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Set caching settings on matching response according to the configurations.
 *
 * The first matching ruleset is applied.
 *
 * @author Lea Haensenberger <lea.haensenberger@gmail.com>
 * @author David Buchmann <mail@davidbu.ch>
 */
class CacheControlSubscriber extends AbstractRuleSubscriber implements EventSubscriberInterface
{
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

        $options = $this->matchConfiguration($request, $response);
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
}
