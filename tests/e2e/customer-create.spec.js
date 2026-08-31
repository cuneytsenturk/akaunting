const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Common domain (Vite POC, see vite.config.js) — contacts.min.js is Vite-built.
test('creates a customer', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/sales/customers/create`);
    await page.waitForLoadState('networkidle');

    const name = `E2E Vite Customer ${Date.now()}`;
    await page.locator('input[name="name"]').fill(name);
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await page.waitForURL(/\/sales\/customers\/\d+$/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name })).toBeVisible();
});
