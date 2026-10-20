// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

/** @module theme_brandboost/appearance */
export const init = () => {
    const config = window.brandboostConfig;
    if (!config || !config.allowSwitch) {
        return;
    }
    document.querySelectorAll('[data-bb-mode-select]').forEach(select => {
        if (select.dataset.initialized) {
            return;
        }
        select.dataset.initialized = 'true';
        const synchronize = () => {
            select.value = document.documentElement.getAttribute('data-bb-mode');
        };
        synchronize();
        select.addEventListener('change', () => {
            if (!['light', 'dark', 'auto'].includes(select.value)) {
                return;
            }
            document.documentElement.setAttribute('data-bb-mode', select.value);
            try {
                localStorage.setItem(config.storageKey, select.value);
            } catch (error) {
                // The choice still works for this page when persistent storage is unavailable.
            }
            window.dispatchEvent(new Event('brandboost:modechange'));
        });
        window.addEventListener('brandboost:modechange', synchronize);
    });
};
