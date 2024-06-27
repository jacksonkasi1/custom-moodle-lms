<?php
define('NO_DEBUG_DISPLAY', true);
define('WS_SERVER', true);

require('../../config.php');
require_once("$CFG->dirroot/webservice/restful/locallib.php");

if (!webservice_protocol_is_enabled('restful')) {
    header("HTTP/1.0 403 Forbidden");
    die;
}

$server = new webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN);

if (strpos($_SERVER['REQUEST_URI'], '/users/all') !== false) {
    $server->run('users/all');
} else {
    $server->run();
}
