<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$container->loadFromExtension('fos_http_cache', array(
    'cache_control' => array(
        'rules' => array(
            array(
                'match' => array(
                    'additional_cacheable_status' => array(100, 500),
                    'match_response' => 'status',
                ),
                'headers' => array(
                    'cache_control' => array(
                        'public' => true,
                    ),
                ),
            ),
        ),
    ),
));
