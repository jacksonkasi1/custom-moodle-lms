<?php
namespace webservice_nmapi;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib/include.php');

$routes = [
    [
        'regex' => '/users',
        'methods' => [
            'POST' => external_api_method('core_user_get_users', [
                'argsmapper' => function($args, $request) {
                    return ['criteria' => []];
                },
                'resultmapper' => function($result) {
                    return $result['users'];
                }
            ]),
        ]
    ],
    [
        'regex' => '/auth',
        'methods' => [
            'POST' => external_api_method('local_nmapi_get_token', [
                'argsmapper' => function($args, $request) {
                    return [];
                },
                'resultmapper' => function($result) {
                    return $result;
                }
            ]),
        ]
    ],
];
