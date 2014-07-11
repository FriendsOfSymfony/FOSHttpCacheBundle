<?php

$container->loadFromExtension('fos_http_cache', array(
    'tags' => array(
        'enabled' => false,
        'rules' => array(
            array(
                'match' => array(
                    'path' => '/def',
                ),
                'tags' => array('a'),
            )
        )
    ),
));
