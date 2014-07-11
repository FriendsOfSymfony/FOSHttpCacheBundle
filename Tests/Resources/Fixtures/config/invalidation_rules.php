<?php

$container->loadFromExtension('fos_http_cache', array(
    'invalidation' => array(
        'enabled' => false,
        'rules' => array(
            array(
                'match' => array(
                    'path' => '/def',
                ),
                'routes' => array(
                    'routename' => array(),
                )
            )
        )
    ),
));
