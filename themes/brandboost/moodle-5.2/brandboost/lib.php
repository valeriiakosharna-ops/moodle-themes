<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

defined('MOODLE_INTERNAL') || die();

/** The brand tokens, shared by settings and the SCSS pipeline. */
function theme_brandboost_palette(): array {
    return [
        'brandcolor' => '#00A6EB',
        'accentcolor' => '#E60036',
        'skycolor' => '#81D0F4',
        'coralcolor' => '#EF958C',
        'inkcolor' => '#2A668E',
        'mauvecolor' => '#937C8A',
        'nightcolor' => '#302A40',
        'winecolor' => '#951B3C',
    ];
}

/** Read a colour safely, including before defaults have been saved. */
function theme_brandboost_colour($theme, string $name): string {
    $value = $theme->settings->{$name} ?? '';
    return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value)
        ? $value : theme_brandboost_palette()[$name];
}

/** Compile Boost itself, or an administrator's uploaded preset. */
function theme_brandboost_get_main_scss_content($theme): string {
    global $CFG;
    $filename = $theme->settings->preset ?? 'default.scss';
    if (!in_array($filename, ['default.scss', 'plain.scss'], true)) {
        $file = get_file_storage()->get_file(
            context_system::instance()->id, 'theme_brandboost', 'preset', 0, '/', $filename
        );
        if ($file) {
            return $file->get_content();
        }
        $filename = 'default.scss';
    }
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/' . $filename);
}

/** Seed Bootstrap before it compiles; administrator overrides run afterwards. */
function theme_brandboost_get_pre_scss($theme): string {
    $scss = '';
    foreach (theme_brandboost_palette() as $name => $default) {
        $scss .= '$bb-' . $name . ': ' . theme_brandboost_colour($theme, $name) . ";\n";
    }
    $widths = ['comfortable' => '1280px', 'wide' => '1520px', 'fluid' => 'none'];
    $width = $widths[$theme->settings->contentwidth ?? 'wide'] ?? $widths['wide'];
    // Seed Boost's own limited-width rules as well as our CSS variable.
    $scss .= '$course-content-maxwidth: ' . $width . ";\n";
    $scss .= <<<'SCSS'
// Keep all primary Bootstrap states tied to the administrator's brand colour.
$primary: $bb-brandcolor;
$danger: $bb-accentcolor;
$body-color: $bb-nightcolor;
$body-bg: #f4f7fb;
$link-color: $bb-inkcolor;
$link-hover-color: darken($bb-inkcolor, 10%);
$border-radius: .75rem;
$border-radius-lg: 1rem;
$border-radius-sm: .5rem;
$font-family-sans-serif: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
SCSS;
    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\n\$behatsite: true;\n";
    }
    // Distinct keys prevent Boost's inherited callbacks from inserting the same code twice.
    return $scss . "\n" . ($theme->settings->customscsspre ?? '');
}

/** Theme styling first, optional backgrounds next, administrator SCSS last. */
function theme_brandboost_get_extra_scss($theme): string {
    $scss = file_get_contents(__DIR__ . '/scss/brand.scss');
    $widths = ['comfortable' => '1280px', 'wide' => '1520px', 'fluid' => 'none'];
    $width = $widths[$theme->settings->contentwidth ?? 'wide'] ?? $widths['wide'];
    $scss .= "\n:root { --bb-content-width: {$width}; }\n";
    foreach (['backgroundimage', 'loginbackgroundimage'] as $area) {
        $url = $theme->setting_file_url($area, $area);
        if (!$url) {
            continue;
        }
        $url = str_replace(["\\", "'", "\r", "\n"], ['%5C', '%27', '', ''], (string)$url);
        $selector = $area === 'backgroundimage' ? 'body' : 'body.pagelayout-login #page .login-layout-left';
        $scss .= "\n{$selector} { background-image: url('{$url}'); background-size: cover; background-position: center; }\n";
    }
    return $scss . "\n" . ($theme->settings->customscss ?? '');
}

/** Serve only public theme setting images, in the system context. */
function theme_brandboost_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_SYSTEM ||
            !in_array($filearea, ['backgroundimage', 'loginbackgroundimage'], true)) {
        send_file_not_found();
    }
    $options += ['cacheability' => 'public'];
    return theme_config::load('brandboost')->setting_file_serve($filearea, $args, $forcedownload, $options);
}
