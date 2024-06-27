<?php
defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'webservice/nmapi:use' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [],
    ],
];
