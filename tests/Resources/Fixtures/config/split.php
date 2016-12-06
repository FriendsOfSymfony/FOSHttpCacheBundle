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
                    'methods' => 'GET,POST',
                    'ips' => '1.2.3.4, 1.1.1.1',
                ],
                'headers' => [
                    'vary' => 'Cookie,Authorization',
                ],
            ],
        ],
    ],
    'proxy_client' => [
        'varnish' => [
            'http' => [
                'servers' => [
                    '1.1.1.1:80',
                    '2.2.2.2:80',
                ],
            ],
        ],
        'nginx' => [
            'http' => [
                'servers' => [
                    '1.1.1.1:81',
                    '2.2.2.2:81',
                ],
            ],
        ],
    ],
]);
