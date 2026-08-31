const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { LOCALE_EMAIL, LOCALE_PASSWORD, COMPANY_ID } = require('./support/constants');

// Cross-cutting smoke test for a bug class found in wizard/Company.vue (see
// webpack-to-vite-roadmap.md 5h): dynamic require()/import() calls that load
// a per-locale file (flatpickr l10n) only execute for non-English locales —
// `lang_split[0] !== 'en'`. Every other spec in this suite runs as an
// en-GB user, so a Rollup incompatibility gated behind that branch would
// never surface. This uses a dedicated de-DE fixture user (never the shared
// admin fixture — its translated UI strings would break other specs'
// text-based assertions) to actually execute that branch.
// Vue 2 catches errors thrown inside lifecycle hooks (created/mounted/etc.)
// internally and logs them via console.error — it does NOT let them bubble
// to window.onerror, so `page.on('pageerror')` never fires for this bug
// class (verified empirically: reintroducing the original unguarded
// require() and rerunning this test with a pageerror-based assertion still
// passed). console 'error' messages are the only observable signal.
function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
        }
    });
    return errors;
}

test.describe('Non-English locale smoke test', () => {
    test('wizard company step (dynamic flatpickr locale import) does not throw require-is-not-defined', async ({ page }) => {
        const consoleErrors = collectConsoleErrors(page);

        await login(page, LOCALE_EMAIL, LOCALE_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/wizard/companies`);
        await expect(page.locator('input[name="tax_number"]')).toBeVisible();

        expect(response.status()).toBe(200);

        // The specific symptom of an unguarded dynamic require() under
        // Rollup — a real one crashed this component's created() hook
        // (see webpack-to-vite-roadmap.md 5h) before this assertion existed.
        const requireErrors = consoleErrors.filter(e => e.includes('require is not defined'));
        expect(requireErrors).toEqual([]);
    });

    test('dashboard renders under a non-English locale', async ({ page }) => {
        await login(page, LOCALE_EMAIL, LOCALE_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
    });
});
