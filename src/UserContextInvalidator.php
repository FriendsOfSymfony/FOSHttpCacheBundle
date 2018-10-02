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

use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

class UserContextInvalidator
{
    const USER_CONTEXT_TAG_PREFIX = 'fos_http_cache_hashlookup-';

    /**
     * @var TagCapable
     */
    private $tagger;

    public function __construct(TagCapable $tagger)
    {
        $this->tagger = $tagger;
    }

    /**
     * Invalidate the user context hash.
     *
     * @param string $sessionId
     */
    public function invalidateContext($sessionId)
    {
        $this->tagger->invalidateTags([static::buildTag($sessionId)]);
    }

    public static function buildTag($hash)
    {
        return static::USER_CONTEXT_TAG_PREFIX.$hash;
    }
}
