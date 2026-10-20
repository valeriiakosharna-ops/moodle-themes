// Functional checks against a disposable, already installed Moodle 5.2 instance.
import assert from 'node:assert/strict';
import {mkdir, readFile, writeFile} from 'node:fs/promises';
import {chromium} from 'playwright';
import AxeBuilder from '@axe-core/playwright';

const origin = process.env.MOODLE_URL || 'http://127.0.0.1:8080';
const courseId = process.env.MOODLE_COURSE_ID;
const password = process.env.MOODLE_PASSWORD || (process.env.MOODLE_PASSWORD_FILE
    ? (await readFile(process.env.MOODLE_PASSWORD_FILE, 'utf8')).trim() : '');
assert.ok(courseId && password, 'Provide MOODLE_COURSE_ID and MOODLE_PASSWORD or MOODLE_PASSWORD_FILE');
await mkdir('test-results', {recursive: true});
const browser = await chromium.launch({
    headless: true,
    ...(process.env.BROWSER_EXECUTABLE_PATH ? {executablePath: process.env.BROWSER_EXECUTABLE_PATH} : {}),
    ...(process.env.BROWSER_ARGS ? {args: JSON.parse(process.env.BROWSER_ARGS)} : {}),
});
const errors = [];
const checks = [];
const scans = {};
const summarize = violations => violations.map(({id, impact, description, nodes}) => ({
    id, impact, description,
    nodes: nodes.map(({target, failureSummary}) => ({target, failureSummary})),
}));
const check = name => { checks.push(name); console.log(`PASS ${name}`); };

try {
    const context = await browser.newContext({viewport: {width: 1440, height: 1000}, reducedMotion: 'reduce'});
    const page = await context.newPage();
    page.setDefaultNavigationTimeout(120000);
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`${origin}/login/index.php`, {waitUntil: 'networkidle'});
    assert.ok(await page.locator('#loginbtn').isVisible());
    assert.equal(await page.locator('.bb-login-mark').count(), 1);
    await page.screenshot({path: 'test-results/login-light.png'});
    await page.locator('#username').fill(process.env.MOODLE_USER || 'admin');
    await page.locator('#password').fill(password);
    await Promise.all([
        page.waitForURL(url => !url.pathname.includes('/login/')),
        page.locator('#loginbtn').click(),
    ]);
    check('native Moodle login');

    const courseUrl = `${origin}/course/view.php?id=${courseId}`;
    await page.goto(courseUrl, {waitUntil: 'networkidle'});
    const initialIndex = page.locator('#theme_boost-drawers-courseindex');
    if (!(await initialIndex.getAttribute('class')).split(' ').includes('show')) {
        await page.locator('[data-action="toggle"][data-target="theme_boost-drawers-courseindex"]').click();
    }
    await page.locator('.courseindex-section').first().waitFor({state: 'visible'});
    assert.equal(await page.locator('.main-inner').evaluate(node => getComputedStyle(node).maxWidth), '1520px');
    assert.ok(await page.locator('.courseindex-section').count() > 1);
    const selector = page.locator('[data-bb-mode-select]');
    await selector.selectOption('light');
    assert.equal(await page.locator('html').getAttribute('data-bs-theme'), 'light');
    await page.screenshot({path: 'test-results/course-light.png'});
    check('course content and grouped native course index');

    for (const mode of ['light', 'dark']) {
        await selector.selectOption(mode);
        // Bootstrap transitions can temporarily lower contrast during the mode change.
        await page.waitForFunction(expected => document.documentElement.dataset.bsTheme === expected, mode);
        scans[mode] = await new AxeBuilder({page}).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
        if (mode === 'dark') {
            await page.screenshot({path: 'test-results/course-dark.png'});
        }
    }
    await writeFile('test-results/accessibility.json', JSON.stringify(Object.fromEntries(
        Object.entries(scans).map(([mode, report]) => [mode, summarize(report.violations)])
    ), null, 2));
    check('light and dark styles rendered; accessibility results collected');
    await page.reload({waitUntil: 'networkidle'});
    assert.equal(await page.locator('html').getAttribute('data-bs-theme'), 'dark');
    assert.equal(await selector.inputValue(), 'dark');
    check('appearance persists across reloads');
    await selector.selectOption('auto');
    await page.emulateMedia({colorScheme: 'dark'});
    await page.waitForFunction(() => document.documentElement.dataset.bsTheme === 'dark');
    await page.emulateMedia({colorScheme: 'light'});
    await page.waitForFunction(() => document.documentElement.dataset.bsTheme === 'light');
    check('system mode reacts to device preference changes');
    await selector.selectOption('light');

    const indexId = 'theme_boost-drawers-courseindex';
    const index = page.locator(`#${indexId}`);
    if (!(await index.getAttribute('class')).split(' ').includes('show')) {
        await page.locator(`[data-action="toggle"][data-target="${indexId}"]`).click();
    }
    await Promise.all([
        page.waitForResponse(response => response.url().includes('/preferences/drawer-open-index') && response.ok()),
        index.locator('[data-action="closedrawer"]').click(),
    ]);
    await page.waitForFunction(id => !document.getElementById(id).classList.contains('show'), indexId);
    await page.reload({waitUntil: 'networkidle'});
    assert.ok(!(await index.getAttribute('class')).split(' ').includes('show'));
    await page.locator(`[data-action="toggle"][data-target="${indexId}"]`).click();
    await page.waitForFunction(id => document.getElementById(id).classList.contains('show'), indexId);
    check('native course drawer closes, remembers state and reopens');

    const section = page.locator('.courseindex-section').nth(1);
    const sectionToggle = section.locator('.courseindex-section-title .courseindex-chevron');
    assert.equal(await sectionToggle.count(), 1);
    const before = await sectionToggle.getAttribute('aria-expanded');
    await sectionToggle.click();
    await page.waitForFunction(({id, before}) => document.getElementById(id)
        .querySelector('.courseindex-chevron').getAttribute('aria-expanded') !== before,
    {id: await section.getAttribute('id'), before});
    check('native section collapse');

    const blockId = 'theme_boost-drawers-blocks';
    const blocks = page.locator(`#${blockId}`);
    if (await blocks.count()) {
        await page.locator(`[data-action="toggle"][data-target="${blockId}"]`).click();
        await page.waitForFunction(id => document.getElementById(id).classList.contains('show'), blockId);
        await blocks.locator('[data-action="closedrawer"]').click();
        await page.waitForFunction(id => !document.getElementById(id).classList.contains('show'), blockId);
        check('native block drawer opens and closes');
    }

    await page.setViewportSize({width: 390, height: 844});
    await page.reload({waitUntil: 'networkidle'});
    const primaryId = 'theme_boost-drawers-primary';
    await page.locator(`[data-action="toggle"][data-target="${primaryId}"]`).click();
    await page.waitForFunction(id => document.getElementById(id).classList.contains('show'), primaryId);
    await page.locator(`#${primaryId} [data-action="closedrawer"]`).click();
    await page.waitForFunction(id => !document.getElementById(id).classList.contains('show'), primaryId);
    const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
    assert.equal(horizontalOverflow, false, 'Course shell must fit a mobile viewport');
    await page.screenshot({path: 'test-results/course-mobile.png'});
    check('mobile navigation opens and closes; no page-level horizontal overflow');

    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(`${origin}/admin/settings.php?section=themesettingbrandboost`, {waitUntil: 'networkidle'});
    assert.ok(await page.locator('[name="s_theme_brandboost_brandcolor"]').count());
    assert.ok(await page.locator('[name="s_theme_brandboost_defaultmode"]').count());
    assert.ok(!(await page.locator('body').innerText()).includes('Exception -'));
    check('theme settings page exposes brand colours and mode configuration');

    assert.equal(errors.length, 0, `Browser errors: ${errors.join('; ')}`);
    check('no uncaught JavaScript errors');
    const violations = Object.fromEntries(Object.entries(scans).map(([mode, report]) => [mode, summarize(report.violations)]));
    await writeFile('test-results/browser-report.json', JSON.stringify({checks, violations}, null, 2));
    assert.equal(violations.light.length + violations.dark.length, 0, 'See test-results/browser-report.json for accessibility failures');
    check('automated WCAG A/AA checks on the sample course, both modes');
    await writeFile('test-results/browser-report.json', JSON.stringify({checks, violations}, null, 2));
} finally {
    await browser.close();
}
