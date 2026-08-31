const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Common domain (Vite POC, see vite.config.js) — dashboards.min.js,
// companies.min.js, reports.min.js and imports.min.js are Vite-built.
// Lighter-weight than the other Common specs (customer-create, item-create,
// invoice-create, bill-create already cover contacts.js/items.js/documents.js
// end to end) — these four don't have a single obvious "create and save"
// flow worth locking in yet, so just prove the page loads and the Vue app
// actually mounted (not just a static shell).
test.describe('Common domain page loads', () => {
    test('company dashboard (root) renders', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Dashboard/);
    });

    test('companies index renders and lists the current company', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/common/companies`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name: 'Companies' })).toBeVisible();
        await expect(page.getByRole('cell', { name: 'My Company' })).toBeVisible();
    });

    test('reports index renders', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/common/reports`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Reports/);
    });

    test('item import page renders', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/common/import/common/items`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name: 'Import Items' })).toBeVisible();
    });
});
