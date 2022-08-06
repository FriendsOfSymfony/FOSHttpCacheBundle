<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Configuration;

use FOS\HttpCacheBundle\Exception\InvalidTagException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Tag extends ConfigurationAnnotation
{
    private $tags;

    private $expression;

    public function __construct(
        $data = [],
        $expression = null
    ) {
        $values = [];
        if (is_string($data)) {
            $values['value'] = $data;
        } else {
            $values = $data;
        }

        $values['expression'] = $values['expression'] ?? $expression;

        parent::__construct($values);
    }

    /**
     * Handle tags given without explicit key.
     *
     * @param string|array $data
     */
    public function setValue($data)
    {
        $this->setTags(is_array($data) ? $data : [$data]);
    }

    /**
     * @param mixed $expression
     */
    public function setExpression($expression)
    {
        // @codeCoverageIgnoreStart
        if (!class_exists(ExpressionLanguage::class)) {
            throw new InvalidConfigurationException('@Tag param uses an expression but the ExpressionLanguage is not available.');
        }
        // @codeCoverageIgnoreEnd
        $this->expression = $expression;
    }

    /**
     * @return mixed
     */
    public function getExpression()
    {
        return $this->expression;
    }

    public function setTags(array $tags)
    {
        foreach ($tags as $tag) {
            if (false !== \strpos($tag, ',')) {
                throw new InvalidTagException($tag, ',');
            }
        }

        $this->tags = $tags;
    }

    public function getTags()
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName(): string
    {
        return 'tag';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray(): bool
    {
        return true;
    }
}
