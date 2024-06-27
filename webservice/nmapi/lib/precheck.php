<?php
namespace webservice_nmapi;

use function webservice_restful\not_found;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/response.php');

function precheck_exists_or_not_found($resourcename, $idx) {
    return function($routeargs) use ($resourcename, $idx) {
        global $DB;
        $exists = $DB->record_exists($resourcename, ['id' => $routeargs[$idx]]);
        return $exists ? null : not_found();
    };
}
