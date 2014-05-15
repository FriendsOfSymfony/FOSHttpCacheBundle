<?php

/**
 * This file is part of the FOSHttpCacheBundle package.
 *
 * Copyright (c) 2014 FOS Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Configuration;

use FOS\HttpCacheBundle\Exception\InvalidTagException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Tag
 *
 * @Annotation
 */
class Tag extends ConfigurationAnnotation
{
    /**
     * @var array
     *
     * tags
     */
    protected $tags;

    /**
     * @var string
     *
     * expression
     */
    protected $expression;

    /**
     * Set value
     *
     * @param mixed $data Data
     *
     * @return Tag self Object
     */
    public function setValue($data)
    {
        $this->setTags(is_array($data) ? $data: array($data));

        return $this;
    }

    /**
     * Set expression
     *
     * @param mixed $expression
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;
    }

    /**
     * @return mixed
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Set tags
     *
     * @param array $tags Tags
     *
     * @return Tag self Object
     *
     * @throws InvalidTagException
     */
    public function setTags(array $tags)
    {
        foreach ($tags as $tag) {
            if (false !== \strpos($tag, ',')) {
                throw new InvalidTagException($tag, ',');
            }
        }

        $this->tags = $tags;

        return $this;
    }

    /**
     * Return tags
     *
     * @return array Tags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName()
    {
        return 'tag';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray()
    {
        return true;
    }
}
