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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * A command to trigger cache invalidation by tag from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class InvalidateTagCommand extends ContainerAwareCommand
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var string
     */
    private $commandName;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fos:httpcache:invalidate:tag')
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

        $this->cacheManager->invalidateTags($tags);
    }}
