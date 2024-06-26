<?php
namespace webservice_nmapi;
defined('MOODLE_INTERNAL') || die();

function bad_request($data) {
    return make_response(400, 'Bad Request', $data);
}

function bad_request_from_throwable(\Throwable $e, $verbose = false) {
    return bad_request(serialize_throwable($e, $verbose));
}

function created($data) {
    return make_response(201, 'Created', $data);
}

function forbidden_from_throwable($e, $verbose = false) {
    return make_response(403, 'Forbidden', serialize_throwable($e, $verbose));
}

function internal_server_error_empty() {
    return make_empty_response(500, 'Internal Server Error');
}

function internal_server_error_from_throwable(\Throwable $e, $verbose = false) {
    return make_response(500, 'Internal Server Error', serialize_throwable($e, $verbose));
}

function make_response($code, $text, $data) {
    return ['code' => $code, 'text' => $text, 'body' => $data];
}

function make_empty_response($code, $text) {
    return ['code' => $code, 'text' => $text];
}

function method_not_allowed() {
    return make_empty_response(405, 'Method Not Allowed');
}

function no_content() {
    return make_empty_response(204, 'No Content');
}

function not_found() {
    return make_empty_response(404, 'Not Found');
}

function ok($data) {
    return make_response(200, 'OK', $data);
}

function serialize_throwable(\Throwable $e, $verbose = false) {
    $data = new \stdClass();
    $data->exception = get_class($e);
    $data->errorcode = $e->errorcode ?? null;
    $data->message = $e->getMessage();
    if ($verbose) {
        $data->trace = $e->getTraceAsString();
        $data->debuginfo = $e->debuginfo ?? null;
    }
    return $data;
}

function traditional_response_from_result($result, $args, $request, $options) {
    if ($request->verb === 'POST') {
        if ($result === null) {
            return no_content();
        }
        return created($result);
    } else if ($request->verb === 'GET' && $result === null) {
        return not_found();
    }
    return ok($result);
}

function traditional_response_for_external_api_error_call(\Throwable $e, $args, $request, $options) {
    $verbose = $options['verbose'] ?? false;
    if ($e instanceof \required_capability_exception) {
        return forbidden_from_throwable($e, $verbose);
    } else if ($e instanceof \dml_missing_record_exception) {
        return bad_request_from_throwable($e, $verbose);
    } else if ($e instanceof \moodle_exception) {
        if (strpos($e->errorcode, 'contextnotvalid') !== false) {
            return forbidden_from_throwable($e, $verbose);
        }
        return internal_server_error_from_throwable($e, $verbose);
    }
    return internal_server_error_from_throwable($e, $verbose);
}
