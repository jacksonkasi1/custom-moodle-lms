<?php

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class webservice_restful_external extends external_api {

    public static function get_users_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_users() {
        // Mock data
        $result = [
            [
                'id' => 1,
                'username' => 'user1',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com'
            ],
            [
                'id' => 2,
                'username' => 'user2',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'email' => 'jane.doe@example.com'
            ]
        ];

        // Debugging statement
        error_log("get_users result: " . print_r($result, true));

        return $result;
    }

    public static function get_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                )
            )
        );
    }
}
