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
 * InvalidatePath
 *
 * @Annotation
 */
class InvalidatePath extends ConfigurationAnnotation
{
    /**
     * @var array
     *
     * Paths
     */
    protected $paths;

    /**
     * Set value
     *
     * @param mixed $data Data
     *
     * @return InvalidatePath self Object
     */
    public function setValue($data)
    {
        $this->setPaths((is_array($data) ? $data: array($data)));
    }

    /**
     * Set paths
     *
     * @param array $paths Paths
     *
     * @return InvalidatePath self Object
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Return paths
     *
     * @return array Paths
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName()
    {
        return 'invalidate_path';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray()
    {
        return true;
    }
}
