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

use Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage as SecurityExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Response;

class ExpressionResponseMatcher implements ResponseMatcherInterface
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var string
     */
    private $expression;

    public function __construct($expression, ExpressionLanguage $expressionLanguage = null)
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

    private function getExpressionLanguage()
    {
        if (!$this->expressionLanguage) {
            $this->expressionLanguage = class_exists(SecurityExpressionLanguage::class)
                ? new SecurityExpressionLanguage()
                : new ExpressionLanguage()
            ;
        }

        return $this->expressionLanguage;
    }
}
