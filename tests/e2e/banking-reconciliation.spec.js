const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Banking domain (Vite POC, see vite.config.js) — reconciliations.min.js is Vite-built.
// Saved as a draft rather than fully "Reconciled" — matching the closing
// balance to the account's real running balance isn't guaranteed in a
// shared test environment with accumulated fixture data.
test('creates a reconciliation as a draft', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/banking/reconciliations/create`);
    await page.waitForLoadState('networkidle');

    // The visible money input (there's also a hidden mirror input with the
    // same name — scope by type to avoid a strict-mode ambiguity).
    await page.locator('input[type="tel"][name="closing_balance"]').fill('100');

    await page.getByRole('button', { name: 'Save as Draft', exact: true }).click();

    await expect(page).toHaveURL(new RegExp(`/${COMPANY_ID}/banking/reconciliations$`), { timeout: 15000 });
    await expect(page.getByText('In Progress').first()).toBeVisible();
});
