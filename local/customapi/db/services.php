<?php
$functions = [
    'local_customapi_get_users' => [
        'classname'   => 'local_customapi\external\get_users',
        'methodname'  => 'execute',
        'classpath'   => 'local/customapi/classes/external/get_users.php',
        'description' => 'Returns a list of users',
        'type'        => 'read',
        'capabilities'=> '',
    ],
];

$services = [
    'Custom API Service' => [
        'functions' => ['local_customapi_get_users'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
