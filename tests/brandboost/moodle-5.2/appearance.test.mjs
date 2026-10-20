import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = readFileSync(new URL('../../../themes/brandboost/moodle-5.2/brandboost/javascript/appearance-init.js', import.meta.url), 'utf8');

function browser({saved = null, defaultMode = 'light', allowSwitch = true, dark = false, blocked = false} = {}) {
    const attributes = {};
    const listeners = {};
    const media = {matches: dark, addEventListener: (type, fn) => { media.change = fn; }};
    const root = {
        style: {},
        setAttribute: (name, value) => { attributes[name] = value; },
        getAttribute: name => attributes[name],
    };
    const window = {
        brandboostConfig: {defaultMode, allowSwitch, storageKey: 'test:mode'},
        matchMedia: () => media,
        addEventListener: (name, fn) => { (listeners[name] ||= []).push(fn); },
        dispatchEvent: event => { for (const listener of listeners[event.type] || []) { listener(event); } },
    };
    vm.runInNewContext(source, {
        window,
        document: {documentElement: root},
        localStorage: {getItem: () => { if (blocked) { throw new Error('Blocked'); } return saved; }},
        Event: class { constructor(type) { this.type = type; } },
    });
    return {attributes, root, media, window};
}

test('default mode is applied synchronously without storage', () => {
    const {attributes, root} = browser();
    assert.equal(attributes['data-bs-theme'], 'light');
    assert.equal(root.style.colorScheme, 'light');
});

test('a valid visitor choice overrides the site default', () => {
    assert.equal(browser({saved: 'dark'}).attributes['data-bs-theme'], 'dark');
});

test('system mode follows device changes, explicit light does not', () => {
    const automatic = browser({saved: 'auto', dark: true});
    assert.equal(automatic.attributes['data-bs-theme'], 'dark');
    automatic.media.matches = false;
    automatic.media.change();
    assert.equal(automatic.attributes['data-bs-theme'], 'light');
    const explicit = browser({saved: 'light', dark: true});
    explicit.media.change();
    assert.equal(explicit.attributes['data-bs-theme'], 'light');
});

test('invalid and inaccessible local storage fall back safely', () => {
    assert.equal(browser({saved: '<script>'}).attributes['data-bs-theme'], 'light');
    assert.equal(browser({blocked: true, defaultMode: 'dark'}).attributes['data-bs-theme'], 'dark');
});

test('disabled switching enforces the administrator choice', () => {
    const {attributes, window} = browser({saved: 'dark', allowSwitch: false});
    window.dispatchEvent({type: 'storage', key: 'test:mode', newValue: 'dark'});
    assert.equal(attributes['data-bs-theme'], 'light');
});

test('other tabs synchronize, removal restores default, unrelated keys are ignored', () => {
    const {attributes, window} = browser();
    window.dispatchEvent({type: 'storage', key: 'other', newValue: 'dark'});
    assert.equal(attributes['data-bs-theme'], 'light');
    window.dispatchEvent({type: 'storage', key: 'test:mode', newValue: 'dark'});
    assert.equal(attributes['data-bs-theme'], 'dark');
    window.dispatchEvent({type: 'storage', key: 'test:mode', newValue: null});
    assert.equal(attributes['data-bs-theme'], 'light');
});

test('the shipped AMD module changes mode, persists it and initializes only once', () => {
    const state = browser({saved: 'dark'});
    const listeners = [];
    const select = {dataset: {}, value: 'light', addEventListener: (name, fn) => { listeners.push(fn); }};
    const exported = {};
    const stored = {};
    const built = readFileSync(new URL('../../../themes/brandboost/moodle-5.2/brandboost/amd/build/appearance.min.js', import.meta.url), 'utf8');
    vm.runInNewContext(built, {
        window: state.window,
        document: {documentElement: state.root, querySelectorAll: () => [select]},
        localStorage: {setItem: (name, value) => { stored[name] = value; }},
        Event: class { constructor(type) { this.type = type; } },
        define: (name, dependencies, factory) => {
            assert.equal(name, 'theme_brandboost/appearance');
            factory(exported);
        },
    });
    exported.init();
    exported.init();
    assert.equal(listeners.length, 1);
    assert.equal(select.value, 'dark');
    select.value = 'light';
    listeners[0]();
    assert.equal(state.attributes['data-bs-theme'], 'light');
    assert.equal(stored['test:mode'], 'light');
});
