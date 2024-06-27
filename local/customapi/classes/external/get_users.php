<?php
namespace local_customapi\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

class get_users extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    public static function execute() {
        global $DB;

        $users = $DB->get_records('user', null, '', 'id, username, firstname, lastname, email');

        $userarray = array();
        foreach ($users as $user) {
            $userarray[] = array(
                'id' => $user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email
            );
        }
        
        return $userarray;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'email' => new external_value(PARAM_TEXT, 'Email address')
                )
            )
        );
    }
}
