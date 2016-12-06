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
                    'path' => '/abc',
                ],
                'headers' => [
                    'last_modified' => 'this is no date',
                ],
            ],
        ],
    ],
]);
