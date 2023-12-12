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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class InvalidateRoute
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $params;

    public function __construct(
        $data = [],
        $params = []
    ) {
        $values = [];
        if (is_string($data)) {
            $values['value'] = $data;
        } else {
            $values = $data;
        }

        $values['params'] = $values['params'] ?? $params;

        foreach ($values as $k => $v) {
            if (!method_exists($this, $name = 'set'.$k)) {
                throw new \RuntimeException(sprintf('Unknown key "%s" for annotation "@%s".', $k, static::class));
            }

            $this->$name($v);
        }
    }

    /**
     * Handle route name given without explicit key.
     *
     * @param string $value The route name
     */
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
        if (!is_array($params)) {
            throw new \RuntimeException('InvalidateRoute params must be an array');
        }
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                if (1 !== count($value) || !isset($value['expression'])) {
                    throw new \RuntimeException(sprintf(
                        '@InvalidateRoute param %s must be string or {"expression"="<expression>"}, %s given',
                        $name,
                        print_r($value, true)
                    ));
                }
                // @codeCoverageIgnoreStart
                if (!class_exists(ExpressionLanguage::class)) {
                    throw new InvalidConfigurationException(sprintf(
                        '@InvalidateRoute param %s uses an expression but the ExpressionLanguage is not available.',
                        $name
                    ));
                }
                // @codeCoverageIgnoreEnd
            }
        }

        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
