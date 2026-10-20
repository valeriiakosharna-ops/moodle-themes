/* Brand Boost, GPL-3.0-or-later. Synchronous head script: no flash of the wrong colour mode. */
(function() {
    'use strict';
    var config = window.brandboostConfig;
    var root = document.documentElement;
    var mode = config.defaultMode;
    if (config.allowSwitch) {
        try {
            var saved = localStorage.getItem(config.storageKey);
            if (['light', 'dark', 'auto'].indexOf(saved) !== -1) {
                mode = saved;
            }
        } catch (error) {
            // Private browsing and storage policies must not prevent rendering.
        }
    }
    var query = window.matchMedia('(prefers-color-scheme: dark)');
    var resolve = function() {
        var chosen = root.getAttribute('data-bb-mode');
        var resolved = chosen === 'auto' ? (query.matches ? 'dark' : 'light') : chosen;
        root.setAttribute('data-bs-theme', resolved);
        root.style.colorScheme = resolved;
    };
    root.setAttribute('data-bb-mode', mode);
    resolve();
    query.addEventListener('change', resolve);
    window.addEventListener('storage', function(event) {
        if (config.allowSwitch && event.key === config.storageKey) {
            root.setAttribute('data-bb-mode', ['light', 'dark', 'auto'].indexOf(event.newValue) !== -1
                ? event.newValue : config.defaultMode);
            resolve();
            window.dispatchEvent(new Event('brandboost:modechange'));
        }
    });
    window.addEventListener('brandboost:modechange', resolve);
}());
