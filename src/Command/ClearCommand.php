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

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCacheBundle\CacheManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to clear the whole cache from the command line.
 *
 * @author Alexander Schranz <alexander@sulu.io>
 */
class ClearCommand extends BaseInvalidateCommand
{
    use PathSanityCheck;

    protected static $defaultName = 'fos:httpcache:clear';

    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     *
     * @param CacheManager|null $cacheManager The cache manager to talk to
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        parent::__construct($cacheManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Clear the HTTP cache.')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command clears the whole cache or, if that is not supported, invalidates all cache entries in the configured caching proxies.

Example:

    <info>php %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheManager = $this->getCacheManager();

        if ($cacheManager->supports(CacheInvalidator::CLEAR)) {
            $this->getCacheManager()->clearCache();

            return 0;
        }

        if ($cacheManager->supports(CacheInvalidator::INVALIDATE)) {
            $this->getCacheManager()->invalidateRegex('.*');

            return 0;
        }

        $output->writeln(
            '<error>The configured HTTP cache does not support "clear" or "invalidate".</error>'
        );

        return 1;
    }
}
