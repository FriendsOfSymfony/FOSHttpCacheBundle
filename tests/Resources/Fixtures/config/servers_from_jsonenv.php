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
        'varnish' => [
            'http' => [
                'servers_from_jsonenv' => '%env(json:VARNISH_SERVERS)%',
                'base_url' => '/test',
                'http_client' => 'acme.guzzle.nginx',
            ],
        ],
    ],
]);
