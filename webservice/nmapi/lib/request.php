<?php
namespace webservice_nmapi;
defined('MOODLE_INTERNAL') || die();

function extract_token_from_request($request) {
    $headers = $request->headers;
    if (!isset($headers['Authorization'])) {
        return null;
    }
    list($type, $token) = explode(' ', $headers['Authorization']);
    return $token;
}

function get_requested_path(\moodle_url $baseurl, $param = '_r') {
    global $SCRIPT;
    $relativepath = false;
    $hasforcedslashargs = false;
    $routepath = $baseurl->get_path();
    if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
        if ((strpos($_SERVER['REQUEST_URI'], $routepath . '/') !== false)
                && isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            $hasforcedslashargs = true;
        }
    }
    if (!$hasforcedslashargs) {
        $relativepath = optional_param($param, false, PARAM_PATH);
    }
    if ($relativepath !== false and $relativepath !== '') {
        return $relativepath;
    }
    $relativepath = false;
    if (stripos($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '') {
            if (strpos($_SERVER['PATH_INFO'], $SCRIPT) === false) {
                $relativepath = clean_param(urldecode($_SERVER['PATH_INFO']), PARAM_PATH);
            }
        }
    } else {
        if (isset($_SERVER['PATH_INFO'])) {
            if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['PATH_INFO'], $_SERVER['SCRIPT_NAME']) === 0) {
                $relativepath = substr($_SERVER['PATH_INFO'], strlen($_SERVER['SCRIPT_NAME']));
            } else {
                $relativepath = $_SERVER['PATH_INFO'];
            }
            $relativepath = clean_param($relativepath, PARAM_PATH);
        }
    }
    if (empty($relativepath) || $relativepath[0] !== '/') {
        return '/';
    }
    return $relativepath;
}

/**
 * Make the request object.
 *
 * @return object
 */
function make_request_object(\moodle_url $baseurl) {
    $headers = getallheaders();
    $body = file_get_contents('php://input');
    $isjson = isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';
    return (object) [
        'verb' => $_SERVER['REQUEST_METHOD'],
        'body' => $body,
        'data' => $isjson ? json_decode($body, true) : null,
        'path' => get_requested_path($baseurl),
        'query' => $_GET,
        'headers' => getallheaders()
    ];
}

function resolve_route_from_request($routes, $request) {
    $route = null;
    $routeargs = [];
    foreach ($routes as $candidate) {
        $matches = [];
        $regex = '~^' . $candidate['regex'] . '$~';
        if (preg_match($regex, $request->path, $matches)) {
            $route = $candidate;
            $routeargs = array_slice($matches, 1);
            break;
        }
    }
    return [$route, $routeargs];
}
