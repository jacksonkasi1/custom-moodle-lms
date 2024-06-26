<?php
$functions = [
    'local_mycustomservice_get_users' => [
        'classname'   => 'local_mycustomservice_external',
        'methodname'  => 'get_users',
        'classpath'   => 'local/mycustomservice/externallib.php',
        'description' => 'Returns a list of users',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails'
    ],
];

$services = [
    'My Custom Service' => [
        'functions' => ['local_mycustomservice_get_users'],
        'restrictedusers' => 0,
        'enabled' => 0,
    ],
];
