<?php
require_once('../../config.php');

// Example route parsing
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Define your custom routes
$routes = [
    'POST' => [
        '/api/users/get-all' => 'local/customapi/endpoint.php'
    ]
];

foreach ($routes[$request_method] as $route => $script) {
    if (preg_match("#^$route$#", $request_uri)) {
        require_once($script);
        exit();
    }
}

// If no route matches, return 404
header("HTTP/1.0 404 Not Found");
echo json_encode(['error' => 'Not Found']);
