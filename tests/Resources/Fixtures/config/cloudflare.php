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
    'proxy_client' => [
        'cloudflare' => [
            'authentication_token' => 'mytoken',
            'zone_identifier' => 'myzone',
        ],
    ],
]);
