const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Common domain (Vite POC, see vite.config.js) — items.min.js is Vite-built.
test('creates an item', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/common/items/create`);
    await page.waitForLoadState('networkidle');

    const name = `E2E Vite Item ${Date.now()}`;
    await page.locator('input[name="name"]').fill(name);
    await page.locator('input[name="sale_price"]').fill('10');
    await page.locator('input[name="purchase_price"]').fill('5');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    // Unlike accounts/customers, item creation redirects to the index list
    // rather than a numbered show page.
    await page.waitForURL(new RegExp(`/${COMPANY_ID}/common/items$`), { timeout: 15000 });
    await expect(page.getByText(name, { exact: true })).toBeVisible();
});
