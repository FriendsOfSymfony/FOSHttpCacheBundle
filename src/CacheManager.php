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

/**
 * The CacheManager is a CacheInvalidator but adds symfony Route support and
 * response tagging to the framework agnostic FOS\HttpCache\CacheInvalidator.
 *
 * @author David de Boer <david@driebit.nl>
 */
class CacheManager extends CacheInvalidator
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * What type of urls to generate.
     *
     * @var bool|string
     */
    private $generateUrlType = UrlGeneratorInterface::ABSOLUTE_PATH;

    /**
     * Constructor.
     *
     * @param ProxyClient           $cache        HTTP cache proxy client
     * @param UrlGeneratorInterface $urlGenerator Symfony route generator
     */
    public function __construct(ProxyClient $cache, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($cache);
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Set what type of URLs to generate.
     *
     * @param bool|string $generateUrlType One of the constants in UrlGeneratorInterface
     */
    public function setGenerateUrlType($generateUrlType)
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
    public function invalidateRoute($name, array $parameters = [], array $headers = [])
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
    public function refreshRoute($route, array $parameters = [], array $headers = [])
    {
        $this->refreshPath($this->urlGenerator->generate($route, $parameters, $this->generateUrlType), $headers);

        return $this;
    }
}
