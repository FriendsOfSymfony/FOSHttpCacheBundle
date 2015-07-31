<?php

$container->loadFromExtension('fos_http_cache', array(
    'cache_manager' => array(
        'enabled' => true,
        'custom_proxy_client' => 'acme.proxy_client',
    ),
));
