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

use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AbstractRuleSubscriber
{
    /**
     * @var array List of arrays with RuleMatcher, settings array.
     */
    private $rulesMap = array();

    /**
     * Add a rule matcher with a list of header directives to apply if the
     * request and response are matched.
     *
     * @param RuleMatcherInterface $ruleMatcher The headers apply to responses matched by this matcher.
     * @param array                $settings    An array of header configuration.
     */
    public function addRule(
        RuleMatcherInterface $ruleMatcher,
        array $settings = array()
    ) {
        $this->rulesMap[] = array($ruleMatcher, $settings);
    }

    /**
     * Return the settings for the current request if any rule matches.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return array|false Settings to apply or false if no rule matched.
     */
    protected function matchRule(Request $request, Response $response)
    {
        foreach ($this->rulesMap as $elements) {
            if ($elements[0]->matches($request, $response)) {
                return $elements[1];
            }
        }

        return false;
    }
}
