<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Twig;

use FOS\HttpCache\ResponseTagger;

/**
 * A Twig extension to allow adding cache tags from twig templates.
 */
class CacheTagExtension extends \Twig_Extension
{
    /**
     * @var ResponseTagger
     */
    private $responseTagger;

    public function __construct(ResponseTagger $responseTagger)
    {
        $this->responseTagger = $responseTagger;
    }

    /**
     * {@inheritdoc}
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
        $this->responseTagger->addTags((array) $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'fos_httpcache_tag_extension';
    }
}
