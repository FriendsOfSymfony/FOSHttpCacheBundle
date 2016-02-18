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
use FOS\HttpCache\Handler\TagHandler;

/**
 * A command to trigger cache invalidation by tag from the command line.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class InvalidateTagCommand extends ContainerAwareCommand
{
    /**
     * @var TagHandler
     */
    private $tagHandler;

    /**
     * @var string
     */
    private $commandName;

    /**
     * If no cache manager is specified explicitly, fos_http_cache.cache_manager
     * is automatically loaded.
     *
     * Passing CacheManager as argument is deprecated and will be restricted to TagHandler in 2.0.
     *
     * @param TagHandler|CacheManager|null $tagHandler  The tag handler to talk to.
     * @param string                       $commandName Name of this command, in case you want to reuse it.
     */
    public function __construct($tagHandler = null, $commandName = 'fos:httpcache:invalidate:tag')
    {
        if (!($tagHandler instanceof TagHandler || $tagHandler instanceof CacheManager || null === $tagHandler)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of TagHandler, CacheManager or null, but got %s',
                    get_class($tagHandler)
                )
            );
        }
        if ($tagHandler instanceof CacheManager) {
            @trigger_error('Passing the CacheManager to '.__CLASS__.' is deprecated since version 1.2 and will be removed in 2.0. Provide the TagHandler instead.', E_USER_DEPRECATED);

        }
        $this->commandName = $commandName;
        $this->tagHandler = $tagHandler;
        parent::__construct();
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

        $this->getTagManager()->invalidateTags($tags);
    }

    /**
     * @return TagHandler|CacheManager
     */
    protected function getTagManager()
    {
        if (!$this->tagHandler) {
            $this->tagHandler = $this->getContainer()->get('fos_http_cache.handler.tag_handler');
        }

        return $this->tagHandler;
    }
}
