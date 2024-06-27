<?php
$functions = array(
    'webservice_restful_get_users' => array(
        'classname'   => 'webservice_restful_external',
        'methodname'  => 'get_users',
        'classpath'   => 'webservice/restful/externallib.php',
        'description' => 'Retrieve the list of users.',
        'type'        => 'read',
    ),
);

$services = array(
    'RESTful Service' => array(
        'functions' => array ('webservice_restful_get_users'),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
