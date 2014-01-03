<?php

namespace FOS\HttpCacheBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class InvalidateRoute extends ConfigurationAnnotation
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $params;

    public function setValue($value)
    {
        $this->setName($value);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName()
    {
        return 'invalidate_route';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray()
    {
        return true;
    }
} 