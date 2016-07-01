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
                    'methods' => 'GET,POST',
                    'ips' => '1.2.3.4, 1.1.1.1',
                ),
                'headers' => array(
                    'vary' => 'Cookie,Authorization',
                ),
            ),
        ),
    ),
    'proxy_client' => array(
        'varnish' => array(
            'servers' => '1.1.1.1:80,2.2.2.2:80',
        ),
        'nginx' => array(
            'servers' => '1.1.1.1:81,2.2.2.2:81',
        ),
    ),
));
