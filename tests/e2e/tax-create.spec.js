const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Settings domain (Vite POC, see vite.config.js) — taxes.min.js is Vite-built.
test('creates a tax rate', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/settings/taxes/create`);
    await page.waitForLoadState('networkidle');

    const name = `E2E Vite Tax ${Date.now()}`;
    await page.locator('input[name="name"]').fill(name);
    await page.locator('input[name="rate"]').fill('5');

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    // Tax creation redirects to the index list, not a numbered show page.
    await page.waitForURL(new RegExp(`/${COMPANY_ID}/settings/taxes$`), { timeout: 15000 });
    await expect(page.getByText(name, { exact: true })).toBeVisible();
});
