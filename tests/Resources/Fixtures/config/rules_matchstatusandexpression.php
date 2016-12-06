<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$container->loadFromExtension('fos_http_cache', [
    'cache_control' => [
        'rules' => [
            [
                'match' => [
                    'additional_cacheable_status' => [100, 500],
                    'match_response' => 'status',
                ],
                'headers' => [
                    'cache_control' => [
                        'public' => true,
                    ],
                ],
            ],
        ],
    ],
]);
