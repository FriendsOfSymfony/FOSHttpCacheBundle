<?php

namespace FOS\HttpCacheBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class InvalidatePath extends ConfigurationAnnotation
{
    /**
     * @var array
     */
    protected $paths;

    public function setValue($data)
    {
        $this->setPaths((is_array($data) ? $data: array($data)));
    }

    /**
     * @param array $paths
     */
    public function setPaths($paths)
    {
        $this->paths = $paths;
    }

    /**
     * @return array
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