<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_brandboost';
$plugin->version = 2026102000;
$plugin->release = '1.0.0';
$plugin->requires = 2026042000; // Moodle 5.2.
$plugin->maturity = MATURITY_BETA;
$plugin->dependencies = ['theme_boost' => 2026042000];
$plugin->supported = [502, 502];
