<?php

$container->loadFromExtension('fos_http_cache', array(
    'cache_control' => array(
        'rules' => array(
            array(
                'match' => array(
                    'additional_cacheable_status' => array(100, 500),
                    'match_response' => 'status',
                ),
                'headers' => array(
                    'cache_control' => array(
                        'public' => true,
                    ),
                ),
            ),
        ),
    ),
));
