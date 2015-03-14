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
     * Whether to skip this response and not set any cache headers.
     *
     * @var bool
     */
    private $skip = false;

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
     * @var string|bool Name of the header or false to add no header.
     */
    private $debugHeader;

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
            KernelEvents::RESPONSE => array('onKernelResponse', 10),
        );
    }

    /**
     * Set whether to skip this response completely.
     *
     * This can be called when other parts of the application took care of all
     * cache headers. No attempt to merge cache headers is made anymore.
     *
     * The debug header is still added if configured.
     *
     * @param bool $skip
     */
    public function setSkip($skip = true)
    {
        $this->skip = $skip;
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
        if ($this->skip || !$this->isRequestSafe($request)) {
            return;
        }

        $options = $this->matchRule($request, $response);
        if (false !== $options) {
            if (!empty($options['cache_control'])) {
                $directives = array_intersect_key($options['cache_control'], $this->supportedDirectives);
                $extraDirectives = array_diff_key($options['cache_control'], $directives);
                if (!empty($directives)) {
                    $this->setCache($response, $directives, $options['overwrite']);
                }
                if (!empty($extraDirectives)) {
                    $this->setExtraCacheDirectives($response, $extraDirectives, $options['overwrite']);
                }
            }

            if (isset($options['reverse_proxy_ttl'])
                && null !== $options['reverse_proxy_ttl']
                && !$response->headers->has('X-Reverse-Proxy-TTL')
            ) {
                $response->headers->set('X-Reverse-Proxy-TTL', (int) $options['reverse_proxy_ttl'], false);
            }

            if (!empty($options['vary'])) {
                $response->setVary($options['vary'], $options['overwrite']);
            }

            if (!empty($options['etag'])
                && ($options['overwrite'] || null === $response->getEtag())
            ) {
                $response->setEtag(md5($response->getContent()));
            }
            if (isset($options['last_modified'])
                && ($options['overwrite'] || null === $response->getLastModified())
            ) {
                $response->setLastModified(new \DateTime($options['last_modified']));
            }
        }
    }

    /**
     * Set cache headers on response.
     *
     * @param Response $response
     * @param array    $directives
     * @param boolean  $overwrite  Whether to keep existing cache headers or to overwrite them.
     */
    private function setCache(Response $response, array $directives, $overwrite)
    {
        if ($overwrite) {
            $response->setCache($directives);

            return;
        }

        if ('no-cache' === $response->headers->get('cache-control')) {
            // this single header is set by default. if its the only thing, we override it.
            $response->setCache($directives);

            return;
        }

        foreach (array_keys($this->supportedDirectives) as $key) {
            $directive = str_replace('_', '-', $key);
            if ($response->headers->hasCacheControlDirective($directive)) {
                $directives[$key] = $response->headers->getCacheControlDirective($directive);
            }
            if ('public' === $directive && $response->headers->hasCacheControlDirective('private')
                || 'private' === $directive && $response->headers->hasCacheControlDirective('public')
            ) {
                unset($directives[$key]);
            }
        }

        $response->setCache($directives);
    }

    /**
     * Add extra cache control directives.
     *
     * @param Response $response
     * @param array    $controls
     * @param boolean  $overwrite Whether to keep existing cache headers or to overwrite them.
     */
    private function setExtraCacheDirectives(Response $response, array $controls, $overwrite)
    {
        $flags = array('must_revalidate', 'proxy_revalidate', 'no_transform', 'no_cache');
        $options = array('stale_if_error', 'stale_while_revalidate');

        foreach ($flags as $key) {
            $flag = str_replace('_', '-', $key);
            if (!empty($controls[$key])
                && ($overwrite || !$response->headers->hasCacheControlDirective($flag))
            ) {
                $response->headers->addCacheControlDirective($flag);
            }
        }

        foreach ($options as $key) {
            $option = str_replace('_', '-', $key);
            if (isset($controls[$key])
                && ($overwrite || !$response->headers->hasCacheControlDirective($option))
            ) {
                $response->headers->addCacheControlDirective($option, $controls[$key]);
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
