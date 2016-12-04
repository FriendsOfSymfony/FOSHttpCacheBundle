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
    'proxy_client' => array(
        'nginx' => array(
            'purge_location' => '/purge',
            'http' => array(
                'servers' => array('22.22.22.22'),
                'base_url' => '/test',
                'http_client' => 'acme.guzzle.nginx',
            ),
        ),
    ),
));
