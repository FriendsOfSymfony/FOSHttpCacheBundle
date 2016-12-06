<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Exception;

class InvalidTagException extends \InvalidArgumentException
{
    public function __construct($tag, $char)
    {
        parent::__construct(sprintf('Tag %s is invalid because it contains %s', $tag, $char));
    }
}
