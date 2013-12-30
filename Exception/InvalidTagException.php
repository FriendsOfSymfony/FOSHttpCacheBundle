<?php

namespace FOS\HttpCacheBundle\Exception;

class InvalidTagException extends \InvalidArgumentException
{
    public function __construct($tag, $char)
    {
        parent:__construct(sprintf('Tag %s is invalid because it contains %s'));
    }
} 