<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'webservice/restful:use' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
        ],
    ],
];
