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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to trigger cache invalidation by tag from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class InvalidateTagCommand extends BaseInvalidateCommand
{
    protected static $defaultName = 'fos:httpcache:invalidate:tag';

    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     *
     * @param CacheManager|null $cacheManager The cache manager to talk to
     */
    public function __construct(CacheManager $cacheManager = null, $commandName = 'fos:httpcache:invalidate:tag')
    {
        if (2 <= func_num_args()) {
            @trigger_error('Passing a command name in the constructor is deprecated and will be removed in version 3', E_USER_DEPRECATED);
            static::$defaultName = func_get_arg(1);
        }
        parent::__construct($cacheManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::$defaultName) // BC with 2.8
            ->setDescription('Invalidate cached content matching the specified tags on all configured caching proxies')
            ->addArgument(
                'tags',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Tags in the response tags header to invalidate'
            )
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command invalidates all cached content matching the specified tags on the configured caching proxies.

Example:

    <info>php %command.full_name% my-tag other-tag </info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tags = $input->getArgument('tags');

        $this->getCacheManager()->invalidateTags($tags);
    }
}
