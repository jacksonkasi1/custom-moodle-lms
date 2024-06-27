<?php
namespace webservice_nmapi;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/response.php');

function external_api_method($functionname, $middlewares = []) {
    $argmapper = $middlewares['argsmapper'] ?? function($args) { return []; };
    $resultmapper = $middlewares['resultmapper'] ?? function($result) { return $result; };
    $responsemaker = $middlewares['responsemaker'] ?? function($result, $args, $request, $options) {
        return traditional_response_from_result($result, $args, $request, $options);
    };
    $erroresponsemaker = $middlewares['errorhandler'] ?? function(\Throwable $e, $args, $request, $options) {
        return traditional_response_for_external_api_error_call($e, $args, $request, $options);
    };

    $responsefromresult = validate_external_return_middleware($functionname)(
        function($result, $args, $request, $options) use ($resultmapper, $responsemaker) {
            return $responsemaker($resultmapper($result), $args, $request, $options);
        }
    );

    $responsefromexception = $erroresponsemaker;

    return [
        'meta' => [
            'external_function' => $functionname
        ],
        'handler' =>
            map_arguments_middleware($argmapper)(
                validate_external_parameters_middleware($functionname)(
                    call_external_function_middleware($functionname, $responsefromresult, $responsefromexception)(
                        function() { return internal_server_error_empty(); }
                    )
                )
            ),
    ];
}

function map_arguments_middleware($mapper) {
    return function($next) use ($mapper) {
        return function($args, $request, $options) use ($mapper, $next) {
            $args = $mapper($args, $request, $options);
            if (!is_array($args)) {
                return internal_server_error_empty();
            }
            return $next($args, $request, $options);
        };
    };
}

function validate_external_parameters_middleware($functionname) {
    return function($next) use ($functionname) {
        return function($args, $request, $options) use ($next, $functionname) {
            try {
                $args = validate_external_function_parameters($functionname, $args);
            } catch (\Throwable $e) {
                return bad_request_from_throwable($e, $options['verbose'] ?? false);
            }
            return $next($args, $request, $options);
        };
    };
}

function call_external_function_middleware($functionname, $responsefromresult, $responsefromexception) {
    return function($next) use ($functionname, $responsefromresult, $responsefromexception) {
        return function($args, $request, $options) use ($next, $functionname, $responsefromresult, $responsefromexception) {
            try {
                $result = execute_external_function($functionname, $args);
            } catch (\Throwable $e) {
                return $responsefromexception($e);
            }
            return $responsefromresult($result, $args, $request, $options);
        };
    };
}

function validate_external_return_middleware($functionname) {
    return function($next) use ($functionname) {
        return function($result, $args, $request, $options) use ($next, $functionname) {
            try {
                $result = validate_external_function_result($functionname, $result);
            } catch (\Throwable $e) {
                return internal_error_from_exception($e, $options['verbose'] ?? false);
            }
            return $next($result, $args, $request, $options);
        };
    };
}

function validate_external_function_parameters($function, $args) {
    $externalfunctioninfo = \core_external\external_api::external_function_info($function);
    $callable = [$externalfunctioninfo->classname, 'validate_parameters'];
    return call_user_func($callable, $externalfunctioninfo->parameters_desc, $args);
}

function execute_external_function($function, $args) {
    $externalfunctioninfo = \core_external\external_api::external_function_info($function);
    $callable = [$externalfunctioninfo->classname, $externalfunctioninfo->methodname];
    return call_user_func_array($callable, array_values($args));
}

function validate_external_function_result($function, $result) {
    $externalfunctioninfo = \core_external\external_api::external_function_info($function);
    if ($externalfunctioninfo->returns_desc !== null) {
        $callable = [$externalfunctioninfo->classname, 'clean_returnvalue'];
        $result = call_user_func($callable, $externalfunctioninfo->returns_desc, $result);
    }
    return $result;
}
