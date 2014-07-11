<?php

$container->loadFromExtension('fos_http_cache', array(
    'cache_manager' => array(
        'enabled' => false,
    ),
    'tags' => array(
        'enabled' => true,
    ),
));
