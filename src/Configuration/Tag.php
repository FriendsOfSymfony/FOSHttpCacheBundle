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
use Symfony\Component\ExpressionLanguage\Expression;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Tag
{
    /**
     * @var string[]
     */
    private array $tags;

    private ?Expression $expression;

    public function __construct(
        string|array $data = [],
        ?Expression $expression = null
    ) {
        $values = [];
        if (is_string($data)) {
            $values['value'] = $data;
        } else {
            $values = $data;
        }

        $values['expression'] = $values['expression'] ?? $expression;

        foreach ($values as $k => $v) {
            if (!method_exists($this, $name = 'set'.$k)) {
                throw new \RuntimeException(sprintf('Unknown key "%s" for attribute "%s".', $k, static::class));
            }

            $this->$name($v);
        }
    }

    /**
     * Handle tags given without explicit key.
     *
     * @param string|string[] $data
     */
    public function setValue(string|array $data): void
    {
        $this->setTags(is_array($data) ? $data : [$data]);
    }

    public function setExpression(?Expression $expression): void
    {
        $this->expression = $expression;
    }

    public function getExpression(): ?Expression
    {
        return $this->expression;
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {
        foreach ($tags as $tag) {
            if (str_contains($tag, ',')) {
                throw new InvalidTagException($tag, ',');
            }
        }

        $this->tags = $tags;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
