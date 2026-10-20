<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');

$THEME->name = 'brandboost';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->scss = function($theme) {
    return theme_brandboost_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_brandboost_get_pre_scss';
$THEME->extrascsscallback = 'theme_brandboost_get_extra_scss';
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->activityheaderconfig = ['notitle' => true];
// Layouts and templates deliberately come from the installed version of Boost.
