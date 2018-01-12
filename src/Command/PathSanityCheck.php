<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Command;

use FOS\HttpCache\Exception\InvalidArgumentException;

trait PathSanityCheck
{
    /**
     * Check if the path looks like a regular expression.
     *
     * A sane path is non-empty and and contains no characters that are usually
     * found in regular expressions: does not start with ^ or end with $ and
     * does not contain the patterns .* or .+ or ().
     *
     * @param string $path
     *
     * @return bool Whether the path looks like it could be a regular expression
     */
    private function looksLikeRegularExpression($path)
    {
        if (strlen($path) < 1) {
            throw new InvalidArgumentException('Path to invalidate can not be empty. To invalidate the root path, use "/"');
        }

        if ('^' === $path[0]
            || '$' === substr($path, -1)
            || preg_match('/(\.[\*\+]|\(|\))/', $path)
        ) {
            return true;
        }

        return false;
    }
}
