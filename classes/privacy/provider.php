<?php

namespace block_completion_progress\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
