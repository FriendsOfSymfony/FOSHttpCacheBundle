<?php

$container->loadFromExtension('fos_http_cache', array(
    'cache_control' => array(
        'rules' => array(
            array(
                'match' => array(
                    'path' => '/abc',
                ),
                'headers' => array(
                    'last_modified' => 'this is no date',
                ),
            ),
        ),
    ),
));
