<?php
define('NO_DEBUG_DISPLAY', true);
define('WS_SERVER', true);
require(__DIR__ . '/../../config.php');
require(__DIR__ . '/routes/index.php');
$server = new \webservice_nmapi\server($routes);
$server->run();
