<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Exception;

/**
 * Class InvalidTagException
 */
class InvalidTagException extends \InvalidArgumentException
{
    /**
     * Constructor
     *
     * @param string $tag  Tag
     * @param int    $char Char contained in Tag
     */
    public function __construct($tag, $char)
    {
        parent::__construct(sprintf('Tag %s is invalid because it contains %s'));
    }
}
