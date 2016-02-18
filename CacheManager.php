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
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Symfony\Component\HttpFoundation\Response;
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
     * Constructor
     *
     * @param ProxyClientInterface  $cache        HTTP cache proxy client
     * @param UrlGeneratorInterface $urlGenerator Symfony route generator
     */
    public function __construct(ProxyClientInterface $cache, UrlGeneratorInterface $urlGenerator)
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
     * Assign cache tags to a response
     *
     * @param Response $response
     * @param array    $tags
     * @param bool     $replace  Whether to replace the current tags on the
     *                           response
     *
     * @return $this
     *
     * @deprecated Add tags with TagHandler::addTags and then use TagHandler::tagResponse
     */
    public function tagResponse(Response $response, array $tags, $replace = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use the TagHandler instead.', E_USER_DEPRECATED);

        if (!$replace && $response->headers->has($this->getTagsHeader())) {
            $header = $response->headers->get($this->getTagsHeader());
            if ('' !== $header) {
                $tags = array_merge(
                    explode(',', $response->headers->get($this->getTagsHeader())),
                    $tags
                );
            }
        }

        $uniqueTags = array_unique($tags);
        $response->headers->set($this->getTagsHeader(), implode(',', $uniqueTags));

        return $this;
    }

    /**
     * Invalidate a route
     *
     * @param string $name       Route name
     * @param array  $parameters Route parameters (optional)
     * @param array  $headers    Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function invalidateRoute($name, array $parameters = array(), array $headers = array())
    {
        $this->invalidatePath($this->urlGenerator->generate($name, $parameters, $this->generateUrlType), $headers);

        return $this;
    }

    /**
     * Refresh a route
     *
     * @param string $route      Route name
     * @param array  $parameters Route parameters (optional)
     * @param array  $headers    Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function refreshRoute($route, array $parameters = array(), array $headers = array())
    {
        $this->refreshPath($this->urlGenerator->generate($route, $parameters, $this->generateUrlType), $headers);

        return $this;
    }
}
