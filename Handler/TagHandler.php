<?php

namespace FOS\HttpCacheBundle\Handler;

use FOS\HttpCache\Handler\TagHandler as BaseTagHandler;
use Symfony\Component\HttpFoundation\Response;

class TagHandler extends BaseTagHandler
{
    /**
     * Tag response with the previously added tags.
     *
     * @param Response $response
     * @param bool     $replace  Whether to replace the current tags on the
     *                           response. If false, parses the header to merge
     *                           tags.
     *
     * @return $this
     */
    public function tagResponse(Response $response, $replace = false)
    {
        if (!$replace && $response->headers->has($this->getTagsHeaderName())) {
            $header = $response->headers->get($this->getTagsHeaderName());
            if ('' !== $header) {
                $this->addTags(explode(',', $response->headers->get($this->getTagsHeaderName())));
            }
        }

        if ($this->hasTags()) {
            $response->headers->set($this->getTagsHeaderName(), $this->getTagsHeaderValue());
        }

        return $this;
    }
}
