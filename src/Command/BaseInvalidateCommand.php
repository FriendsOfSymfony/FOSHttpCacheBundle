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

use FOS\HttpCacheBundle\CacheManager;
use Symfony\Component\Console\Command\Command;

/**
 * Base class for commands to trigger cache invalidation from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
abstract class BaseInvalidateCommand extends Command
{
    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     */
    public function __construct(
        private CacheManager $cacheManager
    ) {
        parent::__construct();
    }

    protected function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }
}
