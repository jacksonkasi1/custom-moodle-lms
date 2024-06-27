<?php
require_once('../../config.php');
require_once($CFG->libdir . '/externallib.php');

use local_customapi\external\get_users;

$headers = apache_request_headers();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
list($bearer, $token) = explode(' ', $auth_header);

if ($bearer !== 'Bearer' || empty($token)) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Invalid or missing token.']);
    exit();
}

$token_record = $DB->get_record('external_tokens', ['token' => $token]);
if (!$token_record) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Invalid token.']);
    exit();
}

require_login();

$users = get_users::execute();
header('Content-Type: application/json');
echo json_encode($users);
