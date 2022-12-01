<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Http;

use FOS\HttpCache\ResponseTagger;
use Symfony\Component\HttpFoundation\Response;

class SymfonyResponseTagger extends ResponseTagger
{
    /**
     * Tag a symfony response with the previously added tags.
     *
     * @param bool $replace Whether to replace the current tags on the
     *                      response. If false, parses the header to merge
     *                      tags
     *
     * @return $this
     */
    public function tagSymfonyResponse(Response $response, $replace = false)
    {
        if (!$this->hasTags()) {
            return $this;
        }

        if (!$replace && $response->headers->has($this->getTagsHeaderName())) {
            $header = $response->headers->get($this->getTagsHeaderName());
            if ('' !== $header) {
                $this->addTags($this->parseTagsHeaderValue($response->headers->get($this->getTagsHeaderName())));
            }
        }

        $response->headers->set($this->getTagsHeaderName(), $this->getTagsHeaderValue());
        $this->clear();

        return $this;
    }
}
