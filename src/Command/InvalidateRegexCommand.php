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
 * A command to trigger cache invalidation by regular expression from the command line.
 *
 * @author Christian Stocker <chregu@liip.ch>
 * @author David Buchmann <mail@davidbu.ch>
 */
class InvalidateRegexCommand extends BaseInvalidateCommand
{
    protected static $defaultName = 'fos:httpcache:invalidate:regex';

    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     *
     * @param CacheManager|null $cacheManager The cache manager to talk to
     * @param string            $commandName  Deprecated: Do not set this parameter.
     */
    public function __construct(CacheManager $cacheManager = null, $commandName = 'fos:httpcache:invalidate:regex')
    {
        static::$defaultName = $commandName;
        parent::__construct($cacheManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::$defaultName) // BC with 2.8
            ->setDescription('Invalidate everything matching a regular expression on all configured caching proxies')
            ->addArgument(
                'regex',
                InputArgument::REQUIRED,
                'Regular expression for the paths to match.'
            )
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command invalidates all cached content matching a regular expression on the configured caching proxies.

Example:

    <info>php %command.full_name% "/some.*/path" </info>

or clear the whole cache

    <info>php %command.full_name% .</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $regex = $input->getArgument('regex');

        $this->getCacheManager()->invalidateRegex($regex);
    }
}
