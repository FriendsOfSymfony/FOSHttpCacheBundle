<?php

namespace FOS\HttpCacheBundle\Configuration;

use FOS\HttpCacheBundle\Exception\InvalidTagException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class Tag extends ConfigurationAnnotation
{
    protected $tags;
    protected $expression;

    public function setValue($data)
    {
        $this->setTags(is_array($data) ? $data: array($data));
    }

    /**
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