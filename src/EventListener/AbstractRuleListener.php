<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

abstract class AbstractRuleListener
{
    /**
     * @var array List of arrays with RequestMatcherInterface, settings array
     */
    private $rulesMap = [];

    /**
     * Add a rule matcher with a list of header directives to apply if the
     * request and response are matched.
     *
     * @param RequestMatcherInterface $requestMatcher The headers apply to responses matched by this matcher
     * @param array                   $settings       An array of header configuration
     */
    public function addRule(
        RequestMatcherInterface $requestMatcher,
        array $settings = []
    ) {
        $this->rulesMap[] = [$requestMatcher, $settings];
    }

    /**
     * Return the settings for the current request if any rule matches.
     *
     * @return array|false Settings to apply or false if no rule matched
     */
    protected function matchRule(Request $request)
    {
        foreach ($this->rulesMap as $elements) {
            if ($elements[0]->matches($request)) {
                return $elements[1];
            }
        }

        return false;
    }
}
