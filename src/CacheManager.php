<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\ProxyClient\ProxyClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * The CacheManager is a CacheInvalidator but adds symfony Route support and
 * response tagging to the framework agnostic FOS\HttpCache\CacheInvalidator.
 *
 * @author David de Boer <david@driebit.nl>
 */
class CacheManager extends CacheInvalidator
{
    private ProxyClient $cache;

    private UrlGeneratorInterface $urlGenerator;

    /**
     * What type of urls to generate.
     */
    private int $generateUrlType = UrlGeneratorInterface::ABSOLUTE_PATH;

    /**
     * Constructor.
     *
     * @param ProxyClient           $cache        HTTP cache proxy client
     * @param UrlGeneratorInterface $urlGenerator Symfony route generator
     */
    public function __construct(ProxyClient $cache, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($cache);
        $this->cache = $cache;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Set what type of URLs to generate.
     *
     * @param int $generateUrlType One of the constants in UrlGeneratorInterface
     */
    public function setGenerateUrlType(int $generateUrlType): void
    {
        $this->generateUrlType = $generateUrlType;
    }

    /**
     * Invalidate a route.
     *
     * @param string $name       Route name
     * @param array  $parameters Route parameters (optional)
     * @param array  $headers    Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function invalidateRoute(string $name, array $parameters = [], array $headers = []): static
    {
        $this->invalidatePath($this->urlGenerator->generate($name, $parameters, $this->generateUrlType), $headers);

        return $this;
    }

    /**
     * Refresh a route.
     *
     * @param string $route      Route name
     * @param array  $parameters Route parameters (optional)
     * @param array  $headers    Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function refreshRoute(string $route, array $parameters = [], array $headers = []): static
    {
        $this->refreshPath($this->urlGenerator->generate($route, $parameters, $this->generateUrlType), $headers);

        return $this;
    }

    public function flush(): int
    {
        if (!$this->cache instanceof LazyObjectInterface || $this->cache->isLazyObjectInitialized()) {
            return parent::flush();
        }

        return 0;
    }
}
