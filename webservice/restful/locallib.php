<?php
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_settings;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/webservice/lib.php");

class webservice_restful_server extends webservice_base_server {

    protected $responseformat;
    protected $requestformat;

    public function __construct($authmethod) {
        parent::__construct($authmethod);
        $this->wsname = 'restful';
        $this->responseformat = 'json';
        $this->requestformat = 'json';
    }

    private function get_apache_headers() {
        $capitalizearray = [
            'Content-Type',
            'Accept',
            'Authorization',
            'Content-Length',
            'User-Agent',
            'Host',
        ];
        $headers = apache_request_headers();
        $returnheaders = [];

        foreach ($headers as $key => $value) {
            if (in_array($key, $capitalizearray)) {
                $header = 'HTTP_' . strtoupper($key);
                $header = str_replace('-', '_', $header);
                $returnheaders[$header] = $value;
            }
        }

        return $returnheaders;
    }

    private function get_headers($headers = null) {
        $returnheaders = [];

        if (!$headers) {
            $headers = function_exists('apache_request_headers') ? $this->get_apache_headers() : $_SERVER;
        }

        foreach ($headers as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $returnheaders[$key] = $value;
            }
        }

        return $returnheaders;
    }

    private function get_wstoken($headers) {
        if (isset($headers['HTTP_AUTHORIZATION'])) {
            return $headers['HTTP_AUTHORIZATION'];
        } else {
            $ex = new \moodle_exception('noauthheader', 'webservice_restful', '');
            $this->send_error($ex, 401);
        }
    }

    protected function get_users() {
        return external_api::call_external_function('webservice_restful_get_users', array());
    }

    protected function parse_request() {
        parent::set_web_service_call_settings();

        $headers = $this->get_headers();

        if (!($this->token = $this->get_wstoken($headers))) {
            return false;
        }

        if (!($this->responseformat = $this->get_responseformat($headers))) {
            return false;
        }

        if (!($this->requestformat = $this->get_requestformat($headers))) {
            return false;
        }

        if (!($this->functionname = $this->get_wsfunction())) {
            return false;
        }

        if ($this->functionname == 'users/all') {
            $this->functionname = 'webservice_restful_get_users';
        }

        if (empty($this->get_parameters())) {
            $this->parameters = [];
        } else if (!($this->parameters = $this->get_parameters())) {
            return false;
        }

        return true;
    }

    public function run() {
        global $CFG, $SESSION;
    
        raise_memory_limit(MEMORY_EXTRA);
        external_api::set_timeout();
        set_exception_handler([$this, 'exception_handler']);
    
        if (!$this->parse_request()) {
            die;
        }
    
        $this->authenticate_user();
        $this->load_function_info();
    
        $params = [
            'other' => [
                'function' => $this->functionname,
            ],
        ];
        $event = \core\event\webservice_function_called::create($params);
        $event->trigger();
    
        $settings = external_settings::get_instance();
        if (method_exists($settings , 'get_lang')) {
            $sessionlang = $settings->get_lang();
            if (!empty($sessionlang)) {
                $SESSION->lang = $sessionlang;
            }
            setup_lang_from_browser();
            if (empty($CFG->lang)) {
                if (empty($SESSION->lang)) {
                    $CFG->lang = 'en';
                } else {
                    $CFG->lang = $SESSION->lang;
                }
            }
        }
    
        if ($this->functionname == 'webservice_restful_get_users') {
            $this->returns = $this->get_users();
        } else {
            $this->execute();
        }
    
        // Debugging statement
        error_log("Function returns: " . print_r($this->returns, true));
    
        $this->send_response();
        $this->session_cleanup();
        die;
    }
    

    private function get_responseformat($headers) {
        $responseformat = '';

        if (isset($headers['HTTP_ACCEPT'])) {
            $responseformat = ltrim($headers['HTTP_ACCEPT'], 'application/');
        } else {
            $ex = new \moodle_exception('noacceptheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
        }

        return $responseformat;
    }

    private function get_requestformat($headers) {
        $requestformat = '';

        if (isset($headers['HTTP_CONTENT_TYPE'])) {
            $requestformat = ltrim($headers['HTTP_CONTENT_TYPE'], 'application/');
        } else {
            $ex = new \moodle_exception('notypeheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
        }

        return $requestformat;
    }

    private function get_parameters($content='') {
        if (!$content) {
            $content = file_get_contents('php://input');
        }

        if ($this->requestformat == 'json') {
            $parameters = json_decode($content, true);
        } else if ($this->requestformat == 'xml') {
            $parametersxml = simplexml_load_string($content);
            $parameters = json_decode(json_encode($parametersxml), true); 
        } else {  
            $parameters = $_POST;
        }

        return $parameters;
    }

    private function get_wsfunction($getvars=null) {
        $wsfunction = '';

        if ($getvars) { 
            $wsfunction = ltrim($getvars['file'], '/');
        } else if (isset($_GET['file'])) { 
            $wsfunction = ltrim($_GET['file'], '/');
        } else if (isset($_SERVER['PATH_INFO'])) { 
            $wsfunction = ltrim($_SERVER['PATH_INFO'], '/');
        } else if (isset($_SERVER['REQUEST_URI'])) { 
            $wsfunction = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);
        }

        if ($wsfunction == '') {
            $ex = new \moodle_exception('nowsfunction', 'webservice_restful', '');
            $this->send_error($ex, 400);
        }

        return $wsfunction;
    }

    protected function send_response() {
        try {
            if ($this->function->returns_desc != null) {
                $validatedvalues = external_api::clean_returnvalue($this->function->returns_desc, $this->returns);
            } else {
                $validatedvalues = null;
            }
        } catch (Exception $ex) {
            $exception = $ex;
        }
    
        // Debugging statement
        error_log("Validated values: " . print_r($validatedvalues, true));
    
        if (!empty($exception)) {
            $response = $this->generate_error($exception);
        } else {
            if ($this->responseformat == 'json') {
                $response = json_encode($validatedvalues);
            } else {
                $response = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
                $response .= '<RESPONSE>'."\n";
                $response .= self::xmlize_result($validatedvalues, $this->function->returns_desc);
                $response .= '</RESPONSE>'."\n";
            }
        }
    
        $this->send_headers();
        echo $response;
    }



    protected function send_error($ex=null, $code=400) {
        if (!PHPUNIT_TEST) {
            http_response_code($code);
            $this->send_headers($code);
        }
        echo $this->generate_error($ex);
    }

    protected function generate_error($ex) {
        if ($this->responseformat != 'xml') {
            $errorobject = new stdClass;
            $errorobject->exception = get_class($ex);
            if (isset($ex->errorcode)) {
                $errorobject->errorcode = $ex->errorcode;
            }
            $errorobject->message = $ex->getMessage();
            if (debugging() && isset($ex->debuginfo)) {
                $errorobject->debuginfo = $ex->debuginfo;
            }
            $error = json_encode($errorobject);
        } else {
            $error = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
            $error .= '<EXCEPTION class="'.get_class($ex).'">'."\n";
            $error .= '<ERRORCODE>' . htmlspecialchars($ex->errorcode, ENT_COMPAT, 'UTF-8')
                    . '</ERRORCODE>' . "\n";
            $error .= '<MESSAGE>'.htmlspecialchars($ex->getMessage(), ENT_COMPAT, 'UTF-8').'</MESSAGE>'."\n";
            if (debugging() && isset($ex->debuginfo)) {
                $error .= '<DEBUGINFO>'.htmlspecialchars($ex->debuginfo, ENT_COMPAT, 'UTF-8').'</DEBUGINFO>'."\n";
            }
            $error .= '</EXCEPTION>'."\n";
        }
        return $error;
    }

    protected function send_headers($code=200) {
        if ($this->responseformat == 'json') {
            header('Content-type: application/json');
        } else {
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: inline; filename="response.xml"');
        }
        header('X-PHP-Response-Code: '.$code, true, $code);
        header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header('Pragma: no-cache');
        header('Accept-Ranges: none');
        header('Access-Control-Allow-Origin: *');
    }

    protected static function xmlize_result($returns, $desc) {
        if ($desc === null) {
            return '';

        } else if ($desc instanceof external_value) {
            if (is_bool($returns)) {
                $returns = (int)$returns;
            }
            if (is_null($returns)) {
                return '<VALUE null="null"/>'."\n";
            } else {
                return '<VALUE>'.htmlspecialchars($returns, ENT_COMPAT, 'UTF-8').'</VALUE>'."\n";
            }

        } else if ($desc instanceof external_multiple_structure) {
            $mult = '<MULTIPLE>'."\n";
            if (!empty($returns)) {
                foreach ($returns as $val) {
                    $mult .= self::xmlize_result($val, $desc->content);
                }
            }
            $mult .= '</MULTIPLE>'."\n";
            return $mult;

        } else if ($desc instanceof external_single_structure) {
            $single = '<SINGLE>'."\n";
            foreach ($desc->keys as $key => $subdesc) {
                $value = isset($returns[$key]) ? $returns[$key] : null;
                $single .= '<KEY name="'.$key.'">'.self::xmlize_result($value, $subdesc).'</KEY>'."\n";
            }
            $single .= '</SINGLE>'."\n";
            return $single;
        }
    }
}
