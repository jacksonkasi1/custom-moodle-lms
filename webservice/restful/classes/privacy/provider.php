<?php
namespace webservice_restful\privacy;

class provider implements \core_privacy\local\metadata\null_provider {
    use \core_privacy\local\legacy_polyfill;

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
