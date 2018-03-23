<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\UserContext;

use FOS\HttpCache\UserContext\AnonymousRequestMatcher as BaseAnonymousRequestMatcher;

/**
 * Matches anonymous requests using a list of identification headers.
 *
 * @deprecated Use AnonymousRequestMatcher of HttpCache library
 */
class AnonymousRequestMatcher extends BaseAnonymousRequestMatcher
{
    public function __construct(array $options = [])
    {
        @trigger_error(
            'AnonymousRequestMatcher of HttpCacheBundle is deprecated. '.
            'Use AnonymousRequestMatcher of HttpCache library.',
            E_USER_DEPRECATED
        );

        if (isset($options['user_identifier_headers'], $options['session_name_prefix'])) {
            parent::__construct($options);
        } else {
            parent::__construct([
                'user_identifier_headers' => $options,
                'session_name_prefix' => 'PHPSESSID',
            ]);
        }
    }
}
