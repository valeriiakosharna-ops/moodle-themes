<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

namespace theme_brandboost\output;

defined('MOODLE_INTERNAL') || die();

/** Add appearance controls without replacing Boost navigation or page layouts. */
class core_renderer extends \theme_boost\output\core_renderer {
    /** Initialise colour mode before first paint, including on the login page. */
    public function standard_head_html() {
        global $CFG;
        $html = parent::standard_head_html();
        // Maintenance and installation pages must not depend on plugin configuration.
        if (during_initial_install() || $this->page->pagelayout === 'maintenance') {
            return $html;
        }
        $settings = $this->page->theme->settings;
        $mode = $settings->defaultmode ?? 'light';
        $mode = in_array($mode, ['light', 'dark', 'auto'], true) ? $mode : 'light';
        $config = json_encode([
            'defaultMode' => $mode,
            'allowSwitch' => !isset($settings->allowmodeswitch) || !empty($settings->allowmodeswitch),
            // Scope browser preferences to this Moodle site, including subdirectory installs.
            'storageKey' => 'theme_brandboost:' . $CFG->wwwroot . ':mode',
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $script = file_get_contents(__DIR__ . '/../../javascript/appearance-init.js');
        return $html . \html_writer::script('window.brandboostConfig = ' . $config . ';' . $script);
    }

    /** Keep every core/plugin navbar widget and append an accessible mode selector. */
    public function navbar_plugin_output() {
        $output = parent::navbar_plugin_output();
        if (during_initial_install()) {
            return $output;
        }
        $settings = $this->page->theme->settings;
        if (isset($settings->allowmodeswitch) && empty($settings->allowmodeswitch)) {
            return $output;
        }
        $this->page->requires->js_call_amd('theme_brandboost/appearance', 'init');
        return $output . $this->render_from_template('theme_brandboost/appearance', []);
    }
}
