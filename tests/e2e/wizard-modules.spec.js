const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Wizard + Modules domains (Vite POC, see vite.config.js) — wizard/wizard.min.js
// and modules/apps.min.js are Vite-built. The `wizard` middleware doesn't
// block access to its own routes once setting('wizard.completed') is already
// 1 (see app/Http/Middleware/RedirectIfWizardNotCompleted.php — it only
// redirects INTO the wizard when NOT completed, never blocks direct access),
// so these are reachable with the standard admin fixture.
//
// Note: the Install domain (install.js, install/update.js) is NOT covered
// here. Its routes are gated by CanInstall middleware, which 403/redirects
// once APP_INSTALLED=true (this sandbox is already installed). It was
// manually verified — real flow (language select -> submit -> redirect to
// database step) reproduced identically under both Vite and a fresh Mix
// build — by temporarily flipping APP_INSTALLED=false in .env and reverting
// immediately after. That flip isn't safe to run as part of the automated
// suite (a failed test mid-run would leave every other login-dependent test
// broken for the rest of the run), so it's excluded from automation; see
// webpack-to-vite-roadmap.md 5h.
test.describe('Wizard domain', () => {
    test('company step saves and advances to the currencies step', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        await page.goto(`/${COMPANY_ID}/wizard/companies`);
        await page.waitForLoadState('networkidle');

        const taxNumber = `E2EVite${Date.now()}`;
        await page.locator('input[name="tax_number"]').fill(taxNumber);
        await page.getByRole('button', { name: 'Save', exact: true }).click();

        await page.waitForURL(/\/wizard\/currencies/, { timeout: 15000 });
        await expect(page).toHaveURL(/\/wizard\/currencies/);
    });

    test('currencies step renders the current currency list', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/wizard/currencies`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page.getByText('USD', { exact: true }).first()).toBeVisible();
    });
});

test.describe('Modules domain', () => {
    test('API key form validates and reports an invalid key via AJAX', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        await page.goto(`/${COMPANY_ID}/apps/api-key/create`);
        await page.waitForLoadState('networkidle');

        await page.locator('input[name="api_key"]').fill('fake-test-key-1234567890');
        await page.getByRole('button', { name: 'Save', exact: true }).click();

        await expect(page.getByText('The API Key entered is invalid!')).toBeVisible({ timeout: 15000 });
        await expect(page).toHaveURL(/\/apps\/api-key\/create/);
    });
});
