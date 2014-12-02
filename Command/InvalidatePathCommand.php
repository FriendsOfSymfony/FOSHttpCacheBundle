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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use FOS\HttpCacheBundle\CacheManager;

/**
 * A command to trigger cache invalidation by path from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class InvalidatePathCommand extends BaseInvalidateCommand
{
    /**
     * @var string
     */
    private $commandName;

    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     *
     * @param CacheManager|null $cacheManager The cache manager to talk to.
     * @param string            $commandName  Name of this command, in case you want to reuse it.
     */
    public function __construct(CacheManager $cacheManager = null, $commandName = 'fos:httpcache:invalidate:path')
    {
        $this->commandName = $commandName;
        parent::__construct($cacheManager);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription('Invalidate cached paths on all configured caching proxies')
            ->addArgument(
                'paths',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'URL paths you want to invalidate, you can specify any number of paths'
            )
            ->setHelp(<<<EOF
The <info>%command.name%</info> command invalidates a list of paths on the configured caching proxies.

Example:

    <info>php %command.full_name% /some/path /other/path</info>
EOF
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $input->getArgument('paths');

        foreach ($paths as $path) {
            $this->getCacheManager()->invalidatePath($path);
        }
    }
}
