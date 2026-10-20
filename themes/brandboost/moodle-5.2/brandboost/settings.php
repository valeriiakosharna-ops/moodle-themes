<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingbrandboost',
        get_string('pluginname', 'theme_brandboost'));

    $page = new admin_settingpage('theme_brandboost_brand', get_string('brandsettings', 'theme_brandboost'));
    $page->add(new admin_setting_heading('theme_brandboost/identity',
        get_string('identity', 'theme_brandboost'), get_string('identity_desc', 'theme_brandboost')));
    foreach (theme_brandboost_palette() as $name => $default) {
        $setting = new admin_setting_configcolourpicker('theme_brandboost/' . $name,
            get_string($name, 'theme_brandboost'), get_string($name . '_desc', 'theme_brandboost'), $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }
    $choices = [];
    foreach (['light', 'dark', 'auto'] as $mode) {
        $choices[$mode] = get_string('mode_' . $mode, 'theme_brandboost');
    }
    $page->add(new admin_setting_configselect('theme_brandboost/defaultmode',
        get_string('defaultmode', 'theme_brandboost'), get_string('defaultmode_desc', 'theme_brandboost'), 'light', $choices));
    $page->add(new admin_setting_configcheckbox('theme_brandboost/allowmodeswitch',
        get_string('allowmodeswitch', 'theme_brandboost'), get_string('allowmodeswitch_desc', 'theme_brandboost'), 1));
    $widths = [];
    foreach (['comfortable', 'wide', 'fluid'] as $width) {
        $widths[$width] = get_string('width_' . $width, 'theme_brandboost');
    }
    $setting = new admin_setting_configselect('theme_brandboost/contentwidth',
        get_string('contentwidth', 'theme_brandboost'), get_string('contentwidth_desc', 'theme_brandboost'), 'wide', $widths);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    $settings->add($page);

    $page = new admin_settingpage('theme_brandboost_general', get_string('generalsettings', 'theme_boost'));
    $page->add(new admin_setting_configtext('theme_brandboost/unaddableblocks',
        get_string('unaddableblocks', 'theme_boost'), get_string('unaddableblocks_desc', 'theme_boost'),
        'navigation,settings,course_list', PARAM_TEXT));
    $files = get_file_storage()->get_area_files(context_system::instance()->id,
        'theme_brandboost', 'preset', 0, 'filename', false);
    $presets = ['default.scss' => 'default.scss', 'plain.scss' => 'plain.scss'];
    foreach ($files as $file) {
        $presets[$file->get_filename()] = $file->get_filename();
    }
    $setting = new admin_setting_configthemepreset('theme_brandboost/preset',
        get_string('preset', 'theme_boost'), get_string('preset_desc', 'theme_boost'),
        'default.scss', $presets, 'brandboost');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    $setting = new admin_setting_configstoredfile('theme_brandboost/presetfiles',
        get_string('presetfiles', 'theme_boost'), get_string('presetfiles_desc', 'theme_boost'),
        'preset', 0, ['maxfiles' => 20, 'accepted_types' => ['.scss']]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    foreach (['backgroundimage', 'loginbackgroundimage'] as $area) {
        $setting = new admin_setting_configstoredfile('theme_brandboost/' . $area,
            get_string($area, 'theme_boost'), get_string($area . '_desc', 'theme_boost'),
            $area, 0, ['maxfiles' => 1, 'accepted_types' => ['web_image']]);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }
    $settings->add($page);

    $page = new admin_settingpage('theme_brandboost_advanced', get_string('advancedsettings', 'theme_boost'));
    foreach (['customscsspre' => 'rawscsspre', 'customscss' => 'rawscss'] as $name => $label) {
        $setting = new admin_setting_scsscode('theme_brandboost/' . $name,
            get_string($label, 'theme_boost'), get_string($label . '_desc', 'theme_boost'), '', PARAM_RAW);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }
    $settings->add($page);
}
