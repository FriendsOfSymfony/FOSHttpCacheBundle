<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Http\ResponseMatcher;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Response;

class ExpressionResponseMatcher implements ResponseMatcherInterface
{
    private ?ExpressionLanguage $expressionLanguage;
    private string $expression;

    public function __construct(string $expression, ExpressionLanguage $expressionLanguage = null)
    {
        $this->expression = $expression;
        $this->expressionLanguage = $expressionLanguage;
    }

    public function matches(Response $response)
    {
        return $this->getExpressionLanguage()->evaluate(
            $this->expression,
            ['response' => $response]
        );
    }

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!$this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }
}
