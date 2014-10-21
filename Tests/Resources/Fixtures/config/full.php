<?php

$container->loadFromExtension('fos_http_cache', array(
    'cache_control' => array(
        'defaults' => array(
            'overwrite' => true
        ),
        'rules' => array(
            array(
                'match' => array(
                    'path' => '/abc',
                    'host' => 'fos',
                    'methods' => array('GET', 'POST'),
                    'ips' => array('1.2.3.4', '1.1.1.1'),
                    'attributes' => array('_controller' => 'fos.user_bundle.*'),
                    'additional_cacheable_status' => array(100, 500),
                ),
                'headers' => array(
                    'overwrite' => false,
                    'cache_control' => array(
                        'max_age' => 1,
                        's_maxage' => 2,
                        'public' => true,
                        'must_revalidate' => true,
                        'proxy_revalidate' => false,
                        'no_transform' => true,
                        'no_cache' => false,
                        'stale_if_error' => 3,
                        'stale_while_revalidate' => 4,
                    ),
                    'last_modified' => '-1 hour',
                    'reverse_proxy_ttl' => 42,
                    'vary' => array('Cookie', 'Authorization'),
                ),
            ),
        ),
    ),
    'proxy_client' => array(
        'varnish' => array(
            'servers' => array('22.22.22.22'),
            'base_url' => '/test',
            'guzzle_client' => 'acme.guzzle.varnish',
        ),
    ),

    'cache_manager' => array(
        'enabled' => true,
    ),
    'tags' => array(
        'header' => 'FOS-Tags',
        'rules' => array(
            array(
                'match' => array(
                    'path' => '/def',
                    'host' => 'friends',
                    'methods' => array('PUT', 'DELETE'),
                    'ips' => '99.99.99.99',
                    'attributes' => array(
                        '_foo' => 'bar',
                    ),
                    'additional_cacheable_status' => array(501, 502),
                ),
                'tags' => array('a', 'b'),
                'tag_expressions' => array('"a"', '"b"'),
            ),
        ),
    ),
    'invalidation' => array(
        'enabled' => 'auto',
        'rules' => array(
            array(
                'match' => array(
                    'path' => '/hij',
                    'host' => 'symfony',
                    'methods' => array('PATCH'),
                    'ips' => array('42.42.42.42'),
                    'attributes' => array(
                        '_format' => 'json',
                    ),
                    'additional_cacheable_status' => array(404, 403),
                ),
                'routes' => array(
                    'invalidate_route1' => array(
                        'ignore_extra_params' => false,
                    ),
                ),
            ),
        ),
    ),
    'user_context' => array(
        'enabled' => true,
        'match' => array(
            'matcher_service' => 'fos_http_cache.user_context.request_matcher',
            'accept' => 'application/vnd.fos.user-context-hash',
            'method' => 'GET',
        ),
        'hash_cache_ttl' => 300,
        'user_identifier_headers' => array('Cookie', 'Authorization'),
        'user_hash_header' => 'FOS-User-Context-Hash',
        'role_provider' => true,
    ),
    'flash_message' => array(
        'enabled' => true,
        'name' => 'flashtest',
        'path' => '/x',
        'host' => 'y',
        'secure' => true,
    ),
    'debug' => array(
        'header' => 'FOS-Cache-Debug',
    ),

));
