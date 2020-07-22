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
 * A command to trigger cache invalidation by path from the command line.
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
            ->setName(static::$defaultName) // BC with 2.8
            ->setDescription('Invalidate the whole http cache.')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command invalidates the whole cache in the configured caching proxies.

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
        } elseif ($cacheManager->supports(CacheInvalidator::INVALIDATE)) {
            $this->getCacheManager()->invalidateRegex('.*');
        } else {
            $output->writeln(
                '<error>The configured http cache does not support "clear" or "invalidate".</error>'
            );

            return 1;
        }

        return 0;
    }
}
