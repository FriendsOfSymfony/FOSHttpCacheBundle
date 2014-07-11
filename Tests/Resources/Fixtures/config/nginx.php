<?php

$container->loadFromExtension('fos_http_cache', array(
    'proxy_client' => array(
        'nginx' => array(
            'servers' => array('22.22.22.22'),
            'base_url' => '/test',
            'purge_location' => '/purge',
        ),
    ),
));
