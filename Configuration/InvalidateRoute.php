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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * InvalidateRoute
 *
 * @Annotation
 */
class InvalidateRoute extends ConfigurationAnnotation
{
    /**
     * @var string
     *
     * Name
     */
    protected $name;

    /**
     * @var array
     *
     * Params
     */
    protected $params;

    /**
     * Set value
     *
     * @param string $value Value
     *
     * @return InvalidateRoute self Object
     */
    public function setValue($value)
    {
        $this->setName($value);

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name Name
     *
     * @return InvalidateRoute self Object
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set params
     *
     * @param array $params Params
     *
     * @return InvalidateRoute self Object
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Return params
     *
     * @return array Params
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
