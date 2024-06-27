<?php
namespace webservice_nmapi;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');
require_once(__DIR__ . '/../lib/include.php');

class server extends \webservice_base_server {
    protected $token;
    protected $functionname;
    protected $wsname = 'nmapi';
    protected $verbose;
    protected $request;
    protected $response;
    protected $route;
    protected $routeargs;
    protected $routes;
    protected $baseurl;
    
    public function __construct(array $routes) {
        global $CFG;
        parent::__construct(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN);
        $this->baseurl = new \moodle_url('/webservice/nmapi/index.php');
        $this->routes = $routes;
        $this->verbose = $CFG->debugdeveloper ? true : false;
    }
    
    protected function parse_request() {
        $request = make_request_object($this->baseurl);
        list($route, $routeargs) = resolve_route_from_request($this->routes, $request);
        $this->token = extract_token_from_request($request);
        if (!$route) {
            $this->early_bail(not_found());
        }
        if (!isset($route['methods'][$request->verb])) {
            $this->early_bail(method_not_allowed());
        }
        if (isset($route['precheck'])) {
            $precheck = $route['precheck']($routeargs);
            if ($precheck !== null) {
                $this->early_bail($precheck);
            }
            unset($precheck);
        }
        $method = get_method_from_route($route, $request->verb);
        $this->request = $request;
        $this->route = $route;
        $this->routeargs = $routeargs;
        $this->method = $method;
        $functionname = $method['meta']['external_function'] ?? null;
        if (!$functionname) {
            $this->early_bail(internal_server_error_empty());
        }
        $this->functionname = $functionname;
    }

    protected function early_bail($response) {
        $this->response = $response;
        $this->send_response();
        die();
    }
    
    protected function execute() {
        $handler = $this->method['handler'];
        $this->response = $handler($this->routeargs, $this->request, [
            'verbose' => $this->verbose
        ]);
    }
    
    protected function send_response() {
        $response = $this->response;
        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response['code'] . ' ' . $response['text']);
        header('Content-Type: application/json');
        if (array_key_exists('body', $response)) {
            echo json_encode($response['body']);
        }
    }
    
    protected function send_error($e = null) {
        $response = internal_server_error_from_throwable($e, $this->verbose);
        if ($e instanceof \moodle_exception) {
            if ($e->errorcode == 'invalidtoken') {
                $response = forbidden_from_throwable($e, $this->verbose);
            }
        }
        $this->response = $response;
        $this->send_response();
    }
}
