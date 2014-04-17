<?php

namespace FOS\HttpCacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use FOS\HttpCacheBundle\CacheManager;

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
