<?php
// Run this script with the path to an installed disposable Moodle's config.php.
// Temporary preset files are removed; site configuration is never changed.
define('CLI_SCRIPT', true);
require($argv[1]);
require_once($CFG->dirroot . '/theme/brandboost/lib.php');

$checks = 0;
function verify($condition, string $message): void {
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo 'PASS ' . $message . PHP_EOL;
}

$theme = clone theme_config::load('brandboost');
$theme->settings = clone $theme->settings;
verify($theme->parents === ['boost'], 'parent theme is Boost');
verify($theme->haseditswitch && $theme->usescourseindex, 'native editing and course index remain enabled');
verify(count(theme_brandboost_palette()) === 8, 'all eight brand colours are present');
$theme->settings->brandcolor = '#123456';
verify(theme_brandboost_colour($theme, 'brandcolor') === '#123456', 'administrator primary colour is respected');
$theme->settings->brandcolor = '#123456; body {display:none}';
verify(theme_brandboost_colour($theme, 'brandcolor') === '#00A6EB', 'invalid colour falls back without SCSS injection');
$theme->settings->brandcolor = '#00A6EB';
$theme->settings->customscsspre = '$primary: #123456;';
$pre = theme_brandboost_get_pre_scss($theme);
verify(strpos($pre, '$course-content-maxwidth: 1520px;') !== false, 'native Boost content width follows the theme setting');
verify(strpos($pre, '$bb-brandcolor: #00A6EB;') !== false, 'brand tokens reach the SCSS compiler');
verify(str_ends_with($pre, '$primary: #123456;'), 'administrator pre-SCSS runs after theme defaults');

foreach (['default.scss', 'plain.scss'] as $preset) {
    $theme->settings->preset = $preset;
    verify(theme_brandboost_get_main_scss_content($theme) ===
        file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/' . $preset), 'native preset loads: ' . $preset);
}

$fs = get_file_storage();
$preset = $fs->create_file_from_string([
    'contextid' => context_system::instance()->id,
    'component' => 'theme_brandboost',
    'filearea' => 'preset',
    'itemid' => 0,
    'filepath' => '/',
    'filename' => 'brandboost-test-' . bin2hex(random_bytes(8)) . '.scss',
], ':root { --uploaded-preset-test: 1; }');
try {
    $theme->settings->preset = $preset->get_filename();
    verify(theme_brandboost_get_main_scss_content($theme) === ':root { --uploaded-preset-test: 1; }',
        'uploaded SCSS preset loads from the child theme file area');
    $theme->settings->preset = 'missing-preset.scss';
    verify(theme_brandboost_get_main_scss_content($theme) ===
        file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss'), 'missing preset falls back to Boost');
    $theme->settings->customscss = ':root { --administrator-final: 1; }';
    $theme->settings->contentwidth = 'fluid';
    $extra = theme_brandboost_get_extra_scss($theme);
    verify(str_ends_with($extra, ':root { --administrator-final: 1; }'), 'administrator extra SCSS takes precedence');
    verify(strpos($extra, '--bb-content-width: none;') !== false, 'content width setting reaches generated CSS');
    $theme->settings->contentwidth = 'invalid; value';
    verify(strpos(theme_brandboost_get_extra_scss($theme), '--bb-content-width: 1520px;') !== false,
        'invalid width falls back safely');
} finally {
    $preset->delete();
}

echo $checks . ' server checks passed.' . PHP_EOL;
