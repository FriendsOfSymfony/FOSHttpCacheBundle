<?php

namespace FOS\HttpCacheBundle\Twig;

use FOS\HttpCache\Handler\TagHandler;

/**
 * A Twig extension to allow adding cache tags from twig templates.
 */
class CacheTagExtension extends \Twig_Extension
{
    /**
     * @var TagHandler
     */
    private $tagHandler;

    public function __construct(TagHandler $tagHandler)
    {
        $this->tagHandler = $tagHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('fos_httpcache_tag', array($this, 'addTag')),
        );
    }

    /**
     * Add a single tag or an array of tags to the response.
     *
     * The tag string is *not* further processed, you can't use a comma
     * separated string to pass several tags but need to build a twig array.
     *
     * Calling this twig function adds nothing to the output.
     *
     * @param string|array $tag
     */
    public function addTag($tag)
    {
        $this->tagHandler->addTags((array) $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'fos_httpcache_tag_extension';
    }
}
