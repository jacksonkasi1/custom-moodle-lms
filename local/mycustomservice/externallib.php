<?php

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/externallib.php");

class local_mycustomservice_external extends external_api {

    public static function get_users_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_users() {
        global $DB;

        $params = self::validate_parameters(self::get_users_parameters(), []);
        
        $users = $DB->get_records('user', null, '', 'id, username, firstname, lastname, email');

        echo $users;

        return array_values($users);
    }

    public static function get_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'email' => new external_value(PARAM_TEXT, 'Email address')
                ]
            )
        );
    }
}