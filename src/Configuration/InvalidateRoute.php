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

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class InvalidateRoute
{
    private string $name;

    private array $params;

    public function __construct(
        string|array $data = [],
        array $params = []
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
                throw new \RuntimeException(sprintf('Unknown key "%s" for attribute "%s".', $k, static::class));
            }

            $this->$name($v);
        }
    }

    /**
     * Handle route name given without explicit key.
     *
     * @param string $value The route name
     */
    public function setValue(string $value): void
    {
        $this->setName($value);
    }

    /**
     * Handle route name with explicit key.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setParams(array $params): void
    {
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                if (1 !== count($value) || !array_key_exists('expression', $value)) {
                    throw new \RuntimeException(sprintf(
                        'InvalidateRoute param %s must be string or \'expression\': new Expression(\'<expression>\'), %s given',
                        $name,
                        print_r($value, true)
                    ));
                }
            }
        }

        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
