<?php
namespace webservice_nmapi;
defined('MOODLE_INTERNAL') || die();

function get_method_from_route($route, $httpverb) {
    if (!isset($route['methods'][$httpverb])) {
        return null;
    }
    return $route['methods'][$httpverb];
}
