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
 * A command to trigger cache invalidation by tag from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class InvalidateTagCommand extends BaseInvalidateCommand
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
    public function __construct(CacheManager $cacheManager = null, $commandName = 'fos:httpcache:invalidate:tag')
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
            ->setDescription('Invalidate cached content matching the specified tags on all configured caching proxies')
            ->addArgument(
                'tags',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Tags in the response tags header to invalidate'
            )
            ->setHelp(<<<EOF
The <info>%command.name%</info> command invalidates all cached content matching the specified tags on the configured caching proxies.

Example:

    <info>php %command.full_name% my-tag other-tag </info>
EOF
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tags = $input->getArgument('tags');

        $this->getCacheManager()->invalidateTags($tags);
    }
}
