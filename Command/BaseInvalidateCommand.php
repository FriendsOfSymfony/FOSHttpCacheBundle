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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Base class for commands to trigger cache invalidation from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
abstract class BaseInvalidateCommand extends ContainerAwareCommand
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     *
     * @param CacheManager|null $cacheManager The cache manager to talk to.
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        $this->cacheManager = $cacheManager;
        parent::__construct();
    }

    /**
     * Get the configured cache manager, loading fos_http_cache.cache_manager
     * from the container if none was specified.
     *
     * @return CacheManager
     */
    protected function getCacheManager()
    {
        if (!$this->cacheManager) {
            $this->cacheManager = $this->getContainer()->get('fos_http_cache.cache_manager');
        }

        return $this->cacheManager;
    }
}
