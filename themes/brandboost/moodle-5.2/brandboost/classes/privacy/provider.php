<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

namespace theme_brandboost\privacy;

defined('MOODLE_INTERNAL') || die();

/** No server-side personal data is stored by the theme. */
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
