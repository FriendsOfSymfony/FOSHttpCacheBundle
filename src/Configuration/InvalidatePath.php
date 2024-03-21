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
class InvalidatePath
{
    /**
     * @var string[]
     */
    private array $paths;

    /**
     * @param string|string[] $data
     */
    public function __construct(
        string|array $data = []
    ) {
        $values = [];
        if (is_string($data)) {
            $values['value'] = $data;
        } else {
            $values = $data;
        }

        foreach ($values as $k => $v) {
            if (!method_exists($this, $name = 'set'.$k)) {
                throw new \RuntimeException(sprintf('Unknown key "%s" for attribute "%s".', $k, static::class));
            }

            $this->$name($v);
        }
    }

    /**
     * Handle path given without explicit key.
     *
     * @param string|string[] $data
     */
    public function setValue(string|array $data): void
    {
        $this->setPaths(is_array($data) ? $data : [$data]);
    }

    /**
     * @param string[] $paths
     */
    public function setPaths(array $paths): void
    {
        $this->paths = $paths;
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
